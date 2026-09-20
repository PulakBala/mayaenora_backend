<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Category')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Category Name')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('code')
                                ->label('Ledger Code')
                                ->maxLength(50)
                                ->placeholder('e.g. MKT-01, PKG-02'),
                        ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
