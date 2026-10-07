<?php

namespace App\Filament\Resources\ReferralSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReferralSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Referral Program Rules & Controls')
                    ->description('Configure dynamic commission calculations, qualifying order thresholds, and payout limits.')
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label('Referral Program Active')
                            ->default(true)
                            ->helperText('When active, customer referral links earn commission on qualifying delivered orders.'),

                        Grid::make(2)->schema([
                            Select::make('commission_type')
                                ->label('Commission Calculation Type')
                                ->options([
                                    'percentage' => 'Percentage of Order Subtotal (%)',
                                    'fixed' => 'Fixed Amount per Order (৳)',
                                ])
                                ->default('percentage')
                                ->required(),

                            TextInput::make('commission_value')
                                ->label('Commission Value')
                                ->numeric()
                                ->default(5.00)
                                ->helperText('e.g. Enter 5 for 5%, or 100 for ৳100 fixed amount')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('minimum_order_value')
                                ->label('Minimum Order Value (৳)')
                                ->numeric()
                                ->prefix('৳')
                                ->default(500.00)
                                ->helperText('Orders must equal or exceed this amount to qualify for referrer commission.')
                                ->required(),

                            TextInput::make('minimum_payout_threshold')
                                ->label('Minimum Payout Threshold (৳)')
                                ->numeric()
                                ->prefix('৳')
                                ->default(500.00)
                                ->helperText('Customers must accumulate at least this balance to request a withdrawal.')
                                ->required(),
                        ]),

                        Select::make('reward_frequency')
                            ->label('Reward Frequency')
                            ->options([
                                'first_order_only' => 'Referred Customer\'s First Delivered Order Only (One-time)',
                                'all_orders' => 'All Qualifying Delivered Orders (Recurring Lifetime)',
                            ])
                            ->default('first_order_only')
                            ->helperText('Choose whether the referrer gets rewarded once or on every future order.')
                            ->required(),
                    ]),
            ]);
    }
}

