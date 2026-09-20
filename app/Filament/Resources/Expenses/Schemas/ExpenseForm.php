<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('expense_category_id')
                                ->label('Expense Category')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('amount')
                                ->label('Expense Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->required(),

                            DatePicker::make('expense_date')
                                ->label('Date Incurred')
                                ->default(now())
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('payment_method')
                                ->label('Disbursement Method')
                                ->options([
                                    'cash' => 'Petty Cash',
                                    'bank' => 'Bank Transfer / Cheque',
                                    'bkash' => 'bKash Corporate',
                                    'card' => 'Company Card',
                                ])
                                ->default('cash')
                                ->required(),

                            FileUpload::make('receipt_image')
                                ->label('Voucher / Bill Attachment')
                                ->image()
                                ->directory('expenses/receipts'),
                        ]),

                        Textarea::make('description')
                            ->label('Business Justification / Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
