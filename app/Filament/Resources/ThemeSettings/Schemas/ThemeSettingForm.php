<?php

namespace App\Filament\Resources\ThemeSettings\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ThemeSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Header Appearance')
                    ->description('Customize the top header navigation background and text colors.')
                    ->schema([
                        Grid::make(2)->schema([
                            ColorPicker::make('header_bg_color')
                                ->label('Header Background Color')
                                ->hex()
                                ->required()
                                ->default('#ffffff')
                                ->helperText('Controls the background color of the top header bar across the website.'),

                            ColorPicker::make('header_text_color')
                                ->label('Header Text & Icon Color')
                                ->hex()
                                ->default('#111827')
                                ->helperText('Optional contrast text and icon color for header items.'),
                        ]),
                    ]),

                Section::make('Footer Appearance')
                    ->description('Customize the main footer background and text colors.')
                    ->schema([
                        Grid::make(2)->schema([
                            ColorPicker::make('footer_bg_color')
                                ->label('Footer Background Color')
                                ->hex()
                                ->required()
                                ->default('#111827')
                                ->helperText('Controls the background color of the footer at the bottom of every page.'),

                            ColorPicker::make('footer_text_color')
                                ->label('Footer Text & Link Color')
                                ->hex()
                                ->default('#d1d5db')
                                ->helperText('Controls the text and link color inside the footer.'),
                        ]),
                    ]),

                Section::make('Button & Primary Actions')
                    ->description('Customize primary buttons across the store (Add to Cart, Checkout, Buy Now, Search, etc.).')
                    ->schema([
                        Grid::make(3)->schema([
                            ColorPicker::make('button_bg_color')
                                ->label('Button Background Color')
                                ->hex()
                                ->required()
                                ->default('#FF5B37')
                                ->helperText('Primary color for Add to Cart, Checkout, Submit, and action buttons.'),

                            ColorPicker::make('button_hover_color')
                                ->label('Button Hover Color')
                                ->hex()
                                ->default('#eb4e2a')
                                ->helperText('Color when customer hovers mouse over buttons.'),

                            ColorPicker::make('button_text_color')
                                ->label('Button Text Color')
                                ->hex()
                                ->default('#ffffff')
                                ->helperText('Text color on top of the button background.'),
                        ]),
                    ]),

                Section::make('Body Background Appearance')
                    ->description('Customize the main website body and page background color.')
                    ->schema([
                        Grid::make(2)->schema([
                            ColorPicker::make('body_bg_color')
                                ->label('Body Background Color')
                                ->hex()
                                ->required()
                                ->default('#ffffff')
                                ->helperText('Controls the overall page body background color behind content.'),

                            Toggle::make('is_active')
                                ->label('Enable Custom Theme Colors')
                                ->default(true)
                                ->helperText('When enabled, the frontend will apply these custom theme colors.'),
                        ]),
                    ]),
            ]);
    }
}
