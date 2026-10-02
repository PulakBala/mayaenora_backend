<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner Content & Media')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('tag')
                                ->label('Eyebrow Badge / Tag')
                                ->placeholder('e.g. NEXT-GEN ELECTRONICS')
                                ->maxLength(100),

                            TextInput::make('title')
                                ->label('Title / Heading')
                                ->required()
                                ->maxLength(150),

                            TextInput::make('subtitle')
                                ->label('Subtitle / Tagline')
                                ->maxLength(255),
                        ]),

                        FileUpload::make('image')
                            ->label('Banner Graphic / Image')
                            ->image()
                            ->directory('banners')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('link_url')
                                ->label('Target Action URL')
                                ->placeholder('/shop?category=electronics'),

                            TextInput::make('button_text')
                                ->label('CTA Button Label')
                                ->default('Shop Now'),
                        ]),

                        Grid::make(3)->schema([
                            Select::make('placement')
                                ->options([
                                    'hero_slider' => 'Homepage Hero Slider',
                                    'promo_strip' => 'Promotional Strip',
                                    'sidebar' => 'Category Sidebar',
                                ])
                                ->default('hero_slider')
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Active & Visible')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ]),

                        Grid::make(2)->schema([
                            DateTimePicker::make('start_date')
                                ->label('Start Date (Optional)'),

                            DateTimePicker::make('end_date')
                                ->label('End Date (Optional)'),
                        ]),
                    ]),
            ]);
    }
}
