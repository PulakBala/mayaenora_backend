<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Photo')
                    ->square()
                    ->size(48)
                    ->state(function (Product $record): ?string {
                        $img = $record->primaryImage?->image_url
                            ?? $record->primaryImage?->image_path
                            ?? $record->images->first()?->image_url
                            ?? $record->images->first()?->image_path
                            ?? $record->variants->first()?->image;

                        if (! $img) {
                            return asset('images/products/placeholder.jpg');
                        }

                        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                            return $img;
                        }

                        if (str_starts_with($img, 'products/') || str_starts_with($img, 'gallery/')) {
                            return asset('storage/' . $img);
                        }

                        if (str_starts_with($img, '/')) {
                            return asset(ltrim($img, '/'));
                        }

                        return asset($img);
                    })
                    ->defaultImageUrl(fn () => asset('images/products/placeholder.jpg')),

                TextColumn::make('title')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->weight('bold'),

                TextColumn::make('base_sku')
                    ->label('SKU')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->placeholder('—'),

                TextColumn::make('base_selling_price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('variants_sum_stock_quantity')
                    ->sum('variants', 'stock_quantity')
                    ->label('Total Stock')
                    ->badge()
                    ->color(fn ($state) => (int) $state <= 5 ? 'danger' : 'success'),

                ToggleColumn::make('is_published')
                    ->label('Published'),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name'),

                TernaryFilter::make('is_published')
                    ->label('Published Only'),

                Filter::make('low_stock')
                    ->label('Low Stock Alert (≤ 5 units)')
                    ->query(fn ($query) => $query->whereHas('variants', fn ($vq) => $vq->where('stock_quantity', '<=', 5))),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
