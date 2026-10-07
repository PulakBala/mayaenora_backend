<?php

namespace App\Filament\Resources\ReferralCommissions\Tables;

use App\Models\ReferralCommission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferralCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('referrer.name')
                    ->label('Referrer (Earner)')
                    ->description(fn (ReferralCommission $record) => $record->referrer?->phone ?? $record->referrer?->email)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('referredUser.name')
                    ->label('Referred Customer')
                    ->description(fn (ReferralCommission $record) => $record->referredUser?->phone ?? $record->referredUser?->email)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('order_amount')
                    ->label('Order Value')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn (ReferralCommission $record) => $record->commission_type === 'percentage'
                        ? "{$record->commission_rate}%"
                        : "৳" . number_format($record->commission_rate, 2)),

                TextColumn::make('commission_amount')
                    ->label('Commission Earned')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'approved' => 'Approved / Credited',
                        'cancelled' => 'Cancelled',
                    ]),
            ]);
    }
}

