<?php

namespace App\Filament\Resources\CourierSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourierSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Courier Gateway')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('provider')
                    ->label('Provider Key')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('sandbox_mode')
                    ->label('Environment')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sandbox / Test' : 'Production Live'),

                TextColumn::make('base_url')
                    ->label('Endpoint URL')
                    ->limit(35)
                    ->placeholder('Provider Default'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'steadfast' => 'Steadfast',
                        'pathao' => 'Pathao',
                        'redx' => 'RedX',
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

