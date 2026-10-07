<?php

namespace App\Filament\Resources\ReferralSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_enabled')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('commission_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('commission_value')
                    ->label('Commission')
                    ->formatStateUsing(fn ($record) => $record->commission_type === 'percentage'
                        ? "{$record->commission_value}%"
                        : "৳" . number_format($record->commission_value, 2)),

                TextColumn::make('minimum_order_value')
                    ->label('Min Order')
                    ->money('BDT'),

                TextColumn::make('minimum_payout_threshold')
                    ->label('Min Payout')
                    ->money('BDT'),

                TextColumn::make('reward_frequency')
                    ->label('Frequency')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state === 'first_order_only' ? 'First Order' : 'All Orders'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}

