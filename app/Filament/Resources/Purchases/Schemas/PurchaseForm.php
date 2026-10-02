<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\ProductVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Purchase Voucher & Supplier')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('reference_no')
                                ->label('Supplier Bill / Reference #')
                                ->default(fn () => 'PUR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                                ->required()
                                ->maxLength(60)
                                ->unique(ignoreRecord: true),

                            DatePicker::make('purchase_date')
                                ->label('Purchase Date')
                                ->default(now())
                                ->required(),
                        ]),

                        Grid::make(4)->schema([
                            TextInput::make('total_cost')
                                ->label('Total Invoice Cost')
                                ->numeric()
                                ->prefix('৳')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $total = (float) ($get('total_cost') ?: 0);
                                    $paid = (float) ($get('paid_amount') ?: 0);
                                    $set('due_amount', max(0, $total - $paid));
                                }),

                            TextInput::make('paid_amount')
                                ->label('Paid Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00)
                                ->live()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $total = (float) ($get('total_cost') ?: 0);
                                    $paid = (float) ($get('paid_amount') ?: 0);
                                    $due = max(0, $total - $paid);
                                    $set('due_amount', $due);
                                    if ($paid >= $total && $total > 0) {
                                        $set('payment_status', 'paid');
                                    } elseif ($paid > 0) {
                                        $set('payment_status', 'partial');
                                    } else {
                                        $set('payment_status', 'due');
                                    }
                                }),

                            TextInput::make('due_amount')
                                ->label('Due Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00)
                                ->readOnly(),

                            Select::make('payment_status')
                                ->options([
                                    'paid' => 'Fully Paid',
                                    'partial' => 'Partially Paid',
                                    'due' => 'Payment Due',
                                ])
                                ->default('due')
                                ->required(),
                        ]),

                        Textarea::make('notes')
                            ->label('Procurement Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Purchased Line Items & Stock Receiving')
                    ->description('Add purchased products and quantities. Stock will automatically increase upon voucher creation.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('product_variant_id', null);
                                    })
                                    ->columnSpan(3),

                                Select::make('product_variant_id')
                                    ->label('Variant (Size/Color)')
                                    ->options(function (callable $get) {
                                        $productId = $get('product_id');
                                        if (! $productId) return [];
                                        return ProductVariant::where('product_id', $productId)->get()->mapWithKeys(function ($v) {
                                            $details = trim("{$v->size} {$v->color}");
                                            $label = $v->variant_sku . ($details ? " ({$details})" : '');
                                            return [$v->id => $label];
                                        });
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $variant = ProductVariant::find($state);
                                            if ($variant && $variant->cost_price > 0) {
                                                $set('unit_cost', $variant->cost_price);
                                                $qty = (float) ($get('quantity') ?: 1);
                                                $set('subtotal', round($qty * $variant->cost_price, 2));
                                            }
                                        }
                                    })
                                    ->columnSpan(3),

                                TextInput::make('quantity')
                                    ->label('Qty Received')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        $qty = (float) ($get('quantity') ?: 0);
                                        $cost = (float) ($get('unit_cost') ?: 0);
                                        $set('subtotal', round($qty * $cost, 2));
                                    })
                                    ->columnSpan(2),

                                TextInput::make('unit_cost')
                                    ->label('Unit Cost (৳)')
                                    ->numeric()
                                    ->required()
                                    ->default(0.00)
                                    ->live()
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        $qty = (float) ($get('quantity') ?: 0);
                                        $cost = (float) ($get('unit_cost') ?: 0);
                                        $set('subtotal', round($qty * $cost, 2));
                                    })
                                    ->columnSpan(2),

                                TextInput::make('subtotal')
                                    ->label('Subtotal (৳)')
                                    ->numeric()
                                    ->required()
                                    ->default(0.00)
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->columnSpanFull()
                            ->reorderableWithButtons()
                            ->defaultItems(1),
                    ]),
            ]);
    }
}
