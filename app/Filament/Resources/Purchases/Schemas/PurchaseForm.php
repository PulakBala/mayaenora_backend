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

                            TextInput::make('invoice_number')
                                ->label('Supplier Bill / Invoice #')
                                ->required()
                                ->maxLength(60)
                                ->unique(ignoreRecord: true),

                            DatePicker::make('purchase_date')
                                ->label('Purchase Date')
                                ->default(now())
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('total_amount')
                                ->label('Total Invoice Cost')
                                ->numeric()
                                ->prefix('৳')
                                ->required(),

                            TextInput::make('paid_amount')
                                ->label('Paid Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00),

                            Select::make('payment_status')
                                ->options([
                                    'paid' => 'Fully Paid',
                                    'partial' => 'Partially Paid',
                                    'due' => 'Payment Due',
                                ])
                                ->default('paid')
                                ->required(),
                        ]),

                        Textarea::make('notes')
                            ->label('Procurement Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Purchased Line Items & Stock Receiving')
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
                                    ->columnSpan(2),

                                Select::make('product_variant_id')
                                    ->label('Variant (Size/Color)')
                                    ->options(fn (callable $get) => ProductVariant::where('product_id', $get('product_id'))->get()->mapWithKeys(fn ($v) => [
                                        $v->id => "{$v->variant_sku} - " . trim("{$v->size} {$v->color}"),
                                    ]))
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label('Qty Received')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),

                                TextInput::make('unit_cost_price')
                                    ->label('Unit Cost (৳)')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('subtotal')
                                    ->label('Subtotal (৳)')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(7)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
