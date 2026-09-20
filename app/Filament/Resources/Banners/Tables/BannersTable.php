<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Banner')
                    ->width(120)
                    ->height(60),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('placement')
                    ->badge()
                    ->color('info'),

                TextColumn::make('button_text')
                    ->label('CTA Button'),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->options([
                        'hero_slider' => 'Homepage Hero Slider',
                        'promo_strip' => 'Promotional Strip',
                        'sidebar' => 'Category Sidebar',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
