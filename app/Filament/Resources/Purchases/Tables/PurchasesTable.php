<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_no')
                    ->label('Bill / Reference #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('due_amount')
                    ->label('Due')
                    ->money('BDT')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'due' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Fully Paid',
                        'partial' => 'Partially Paid',
                        'due' => 'Payment Due',
                        default => ucfirst($state),
                    }),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Line Items'),
            ])
            ->defaultSort('purchase_date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),

                SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Fully Paid',
                        'partial' => 'Partially Paid',
                        'due' => 'Payment Due',
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
