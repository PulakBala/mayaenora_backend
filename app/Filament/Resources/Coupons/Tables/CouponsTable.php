<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => $state === 'percent' ? 'warning' : 'info'),

                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($record) => $record->type === 'percent' ? "{$record->discount_value}%" : "৳{$record->discount_value}"),

                TextColumn::make('min_order_amount')
                    ->label('Min Spend')
                    ->money('BDT'),

                TextColumn::make('used_count')
                    ->label('Usage')
                    ->formatStateUsing(fn ($record) => "{$record->used_count} / " . ($record->usage_limit ?: '∞')),

                TextColumn::make('end_date')
                    ->label('Expires At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
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
