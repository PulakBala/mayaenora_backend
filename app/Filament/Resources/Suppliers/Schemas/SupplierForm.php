<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supplier Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Contact Person Name')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('company_name')
                                ->label('Company / Agency Name')
                                ->maxLength(150),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->required()
                                ->maxLength(20),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->maxLength(100),

                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->default('active')
                                ->required(),
                        ]),

                        Textarea::make('address')
                            ->label('Office Address')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('balance')
                            ->label('Current Payable / Receivable Balance')
                            ->numeric()
                            ->prefix('৳')
                            ->default(0.00),
                    ]),
            ]);
    }
}
