<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Hierarchy & Details')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Root Category (Top Level)'),

                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                        TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Media & Badges')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Category Icon / Image')
                                    ->image()
                                    ->directory('categories'),

                                FileUpload::make('banner_image')
                                    ->label('Header Banner Image')
                                    ->image()
                                    ->directory('categories/banners'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_featured')
                                    ->label('Featured Badge')
                                    ->default(false),

                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),

                                TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }
}
