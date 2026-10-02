<?php

namespace App\Filament\Resources\InventoryLogs\Schemas;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product & Variant Selection')
                    ->description('Select the product and specific variant you want to adjust stock for.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->options(fn () => Product::pluck('title', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(fn (callable $set) => $set('product_variant_id', null)),

                            Select::make('product_variant_id')
                                ->label('Product Variant')
                                ->options(function (callable $get) {
                                    $productId = $get('product_id');
                                    if (! $productId) {
                                        return [];
                                    }
                                    return ProductVariant::where('product_id', $productId)->get()->mapWithKeys(function ($v) {
                                        $details = trim("{$v->size} {$v->color}");
                                        $desc = $details ? " - {$details}" : '';
                                        return [$v->id => "{$v->variant_sku}{$desc} (Current Stock: {$v->stock_quantity})"];
                                    });
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->helperText(function (callable $get) {
                                    $variantId = $get('product_variant_id');
                                    if (! $variantId) return null;
                                    $variant = ProductVariant::find($variantId);
                                    return $variant ? "Current available stock: {$variant->stock_quantity} units" : null;
                                }),
                        ]),
                    ]),

                Section::make('Stock Adjustment Operation')
                    ->description('Set whether you are adding stock (in) or deducting stock (out).')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('transaction_type')
                                ->label('Adjustment Reason / Category')
                                ->options([
                                    'adjustment' => 'Manual Correction / Physical Audit',
                                    'purchase' => 'Procurement / Stock In',
                                    'return' => 'Customer Return / Restock',
                                    'damage' => 'Damaged / Expired / Lost',
                                    'sale' => 'Offline / Direct Sale',
                                ])
                                ->default('adjustment')
                                ->required(),

                            Select::make('action_direction')
                                ->label('Stock Action')
                                ->options([
                                    'add' => '➕ Add to Stock (Stock In)',
                                    'subtract' => '➖ Deduct from Stock (Stock Out)',
                                ])
                                ->default('add')
                                ->required()
                                ->dehydrated(false),

                            TextInput::make('quantity')
                                ->label('Adjustment Quantity')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->dehydrated(false)
                                ->helperText('Enter the number of units to add or deduct.'),
                        ]),

                        Textarea::make('remarks')
                            ->label('Adjustment Remarks / Reason')
                            ->placeholder('e.g., Physical inventory audit, Supplier replacement, Damaged in warehouse, Customer walk-in sale...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ]);
    }
}
