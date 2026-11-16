<?php

namespace App\Filament\Resources\FooterSections\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FooterSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Footer Column Details')
                    ->description('Configure the column title, order, and visibility.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('title')
                                ->label('Column Title')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g. Shop, Customer Care, About Maya Enora'),

                            TextInput::make('sort_order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower numbers appear first (left-to-right).'),

                            Toggle::make('is_active')
                                ->label('Active / Visible')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Navigation Links')
                    ->description('Manage the links displayed inside this footer column.')
                    ->schema([
                        Repeater::make('links')
                            ->relationship('links')
                            ->label('Links')
                            ->orderColumn('sort_order')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Link Title')
                                    ->required()
                                    ->placeholder('e.g. All Products, Return Policy'),

                                TextInput::make('url')
                                    ->label('URL / Path')
                                    ->required()
                                    ->placeholder('e.g. /shop, /return-policy'),

                                Toggle::make('open_in_new_tab')
                                    ->label('Open in New Tab')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                    ]),
            ]);
    }
}
