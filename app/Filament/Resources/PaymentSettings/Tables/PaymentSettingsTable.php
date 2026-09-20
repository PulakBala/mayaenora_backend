<?php

namespace App\Filament\Resources\PaymentSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Gateway Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('gateway_key')
                    ->label('Identifier')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('sandbox_mode')
                    ->label('Environment')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sandbox / Test' : 'Production Live'),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('fee_percentage')
                    ->label('Fee %')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('gateway_key')
                    ->label('Gateway')
                    ->options([
                        'sslcommerz' => 'SSLCommerz',
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
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

