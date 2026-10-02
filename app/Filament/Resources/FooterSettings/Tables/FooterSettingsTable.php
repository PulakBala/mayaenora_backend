<?php

namespace App\Filament\Resources\FooterSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FooterSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('copyright_text')
                    ->label('Copyright')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('bottom_slogan')
                    ->label('Slogan')
                    ->limit(30),

                TextColumn::make('brand_bio')
                    ->label('Brand Bio')
                    ->limit(40),

                IconColumn::make('newsletter_enabled')
                    ->label('Newsletter Active')
                    ->boolean(),

                TextColumn::make('contact_phone')
                    ->label('Phone'),

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
