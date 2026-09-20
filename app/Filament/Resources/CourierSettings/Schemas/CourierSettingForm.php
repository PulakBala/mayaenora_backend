<?php

namespace App\Filament\Resources\CourierSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourierSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway Identity & Status')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('provider')
                                ->label('Courier Provider')
                                ->options([
                                    'steadfast' => 'Steadfast Courier (BD)',
                                    'pathao' => 'Pathao Courier (BD)',
                                    'redx' => 'RedX Logistics (BD)',
                                ])
                                ->required()
                                ->disabled(fn ($context) => $context === 'edit'),

                            TextInput::make('name')
                                ->label('Display Label')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g. Steadfast Courier Express'),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Enable Courier for Order Dispatch')
                                ->default(false)
                                ->helperText('When enabled, orders can be dispatched via this gateway.'),

                            Toggle::make('sandbox_mode')
                                ->label('Sandbox / Test Mode')
                                ->default(true)
                                ->helperText('When enabled, consignments are simulated or routed to the provider test API.'),
                        ]),
                    ]),

                Section::make('Authentication Credentials & Gateway Endpoints')
                    ->schema([
                        TextInput::make('base_url')
                            ->label('Base API Endpoint URL')
                            ->placeholder('https://portal.steadfast.com.bd/api/v1')
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('api_key')
                                ->label('API Key / Client ID')
                                ->password()
                                ->revealable()
                                ->required(),

                            TextInput::make('secret_key')
                                ->label('Secret Key / Token')
                                ->password()
                                ->revealable()
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('client_id')
                                ->label('Client ID / Store ID (Optional)')
                                ->placeholder('Used for Pathao / RedX Store ID'),

                            TextInput::make('webhook_secret')
                                ->label('Webhook Secret / Token (Optional)')
                                ->password()
                                ->revealable()
                                ->helperText('Webhook URL: /api/v1/webhooks/courier/{provider}'),
                        ]),
                    ]),
            ]);
    }
}

