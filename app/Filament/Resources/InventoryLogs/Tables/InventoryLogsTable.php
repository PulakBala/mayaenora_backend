<?php

namespace App\Filament\Resources\InventoryLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variant.product.title')
                    ->label('Product')
                    ->searchable()
                    ->limit(25)
                    ->weight('bold'),

                TextColumn::make('variant.variant_sku')
                    ->label('Variant SKU')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('variant_details')
                    ->label('Attributes')
                    ->state(fn ($record) => trim("{$record->variant?->size} {$record->variant?->color}") ?: 'Standard'),

                TextColumn::make('transaction_type')
                    ->label('Transaction')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'danger',
                        'return' => 'info',
                        'adjustment' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('quantity_change')
                    ->label('Change')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : "{$state}")
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('remaining_stock')
                    ->label('Balance After')
                    ->badge()
                    ->color(fn ($state) => $state <= 5 ? 'danger' : 'gray'),

                TextColumn::make('remarks')
                    ->label('Notes / Reason')
                    ->limit(35),

                TextColumn::make('creator.name')
                    ->label('Staff User')
                    ->placeholder('System / Auto'),

                TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('transaction_type')
                    ->options([
                        'sale' => 'Online Sales',
                        'purchase' => 'Purchase Stock In',
                        'return' => 'Customer Return',
                        'adjustment' => 'Manual Adjustment',
                    ]),
            ]);
    }
}
