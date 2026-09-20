<?php

namespace App\Filament\Resources\PaymentSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gateway Identity & Status')
                    ->description('Activate and choose between live production or sandbox test mode.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('gateway_key')
                                ->label('Payment Gateway')
                                ->options([
                                    'sslcommerz' => 'SSLCommerz Bangladesh (Cards, bKash, Nagad, NetBanking)',
                                    'bkash' => 'bKash Merchant Direct (Coming Soon)',
                                    'nagad' => 'Nagad Merchant Direct (Coming Soon)',
                                ])
                                ->required()
                                ->disabled(fn ($context) => $context === 'edit'),

                            TextInput::make('name')
                                ->label('Public Display Name')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g. SSLCommerz Payment Gateway'),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Enable Gateway for Customer Checkout')
                                ->default(true)
                                ->helperText('When enabled, customers can choose this online payment gateway during checkout.'),

                            Toggle::make('sandbox_mode')
                                ->label('Sandbox / Test Mode')
                                ->default(true)
                                ->helperText('When enabled, transactions route to the SSLCommerz sandbox simulation environment.'),
                        ]),
                    ]),

                Section::make('Merchant Credentials & Endpoints')
                    ->description('Securely configure your SSLCommerz merchant Store ID and Password (AES-256 encrypted in database).')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('store_id')
                                ->label('SSLCommerz Store ID')
                                ->password()
                                ->revealable()
                                ->required()
                                ->placeholder('e.g. testbox or your live store ID')
                                ->helperText('Default sandbox store ID is: testbox'),

                            TextInput::make('store_passwd')
                                ->label('SSLCommerz Store Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->placeholder('e.g. qwerty or your live store password')
                                ->helperText('Default sandbox store password is: qwerty'),
                        ]),

                        TextInput::make('base_url')
                            ->label('Custom API Base URL (Optional)')
                            ->placeholder('Leave blank to use default (sandbox: https://sandbox.sslcommerz.com | live: https://securepay.sslcommerz.com)')
                            ->columnSpanFull()
                            ->helperText('Override the gateway endpoint URL if using an enterprise proxy or custom SSLCommerz route.'),
                    ]),

                Section::make('Financial & Settlement Configuration')
                    ->description('Define transaction currency and merchant processing fee tracking.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('currency')
                                ->label('Processing Currency')
                                ->default('BDT')
                                ->required()
                                ->maxLength(10)
                                ->placeholder('BDT'),

                            TextInput::make('fee_percentage')
                                ->label('Gateway Processing Fee (%)')
                                ->numeric()
                                ->default(2.50)
                                ->suffix('%')
                                ->required()
                                ->helperText('Used for automated accounting to track merchant settlement deductions per order.'),
                        ]),
                    ]),
            ]);
    }
}

