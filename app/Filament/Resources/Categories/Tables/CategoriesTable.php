<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->circular()
                    ->label('Icon'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->badge()
                    ->color('info')
                    ->placeholder('Root (Top Level)'),

                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                TernaryFilter::make('is_featured')
                    ->label('Featured Only'),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
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
