<?php

namespace App\Filament\Resources\ThemeSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ThemeSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                ColorColumn::make('header_bg_color')
                    ->label('Header BG')
                    ->copyable()
                    ->copyMessage('Color code copied'),

                ColorColumn::make('footer_bg_color')
                    ->label('Footer BG')
                    ->copyable()
                    ->copyMessage('Color code copied'),

                ColorColumn::make('button_bg_color')
                    ->label('Button Color')
                    ->copyable()
                    ->copyMessage('Color code copied'),

                ColorColumn::make('body_bg_color')
                    ->label('Body BG')
                    ->copyable()
                    ->copyMessage('Color code copied'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
