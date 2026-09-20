<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Brand Name')
                                ->required()
                                ->maxLength(100)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                            TextInput::make('slug')
                                ->label('URL Slug')
                                ->required()
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('website')
                                ->label('Website URL')
                                ->url()
                                ->maxLength(200),

                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->default('active')
                                ->required(),
                        ]),

                        FileUpload::make('logo')
                            ->label('Brand Logo')
                            ->image()
                            ->directory('brands')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
