<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color('gray'),

                ImageColumn::make('receipt_image')
                    ->label('Receipt')
                    ->circular()
                    ->placeholder('None'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(35),

                TextColumn::make('creator.name')
                    ->label('Logged By')
                    ->placeholder('Admin'),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                SelectFilter::make('expense_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),

                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Petty Cash',
                        'bank' => 'Bank Transfer',
                        'bkash' => 'bKash Corporate',
                        'card' => 'Company Card',
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
