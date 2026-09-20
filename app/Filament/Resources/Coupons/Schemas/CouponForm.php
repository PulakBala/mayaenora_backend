<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon Rules & Discounts')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Promo Code')
                                ->required()
                                ->maxLength(50)
                                ->unique(ignoreRecord: true)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                            Select::make('type')
                                ->options([
                                    'percent' => 'Percentage Discount (%)',
                                    'fixed' => 'Fixed Amount Discount (৳)',
                                ])
                                ->default('percent')
                                ->required(),

                            TextInput::make('discount_value')
                                ->label('Discount Value')
                                ->numeric()
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('min_order_amount')
                                ->label('Minimum Order Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00),

                            TextInput::make('max_discount_amount')
                                ->label('Maximum Discount Cap (For % coupons)')
                                ->numeric()
                                ->prefix('৳')
                                ->placeholder('No Limit'),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('usage_limit')
                                ->label('Maximum Uses Limit')
                                ->numeric()
                                ->placeholder('Unlimited'),

                            TextInput::make('used_count')
                                ->label('Times Used')
                                ->numeric()
                                ->disabled()
                                ->default(0),

                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'expired' => 'Expired',
                                ])
                                ->default('active')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            DateTimePicker::make('start_date')
                                ->label('Valid From'),

                            DateTimePicker::make('end_date')
                                ->label('Valid Until'),
                        ]),
                    ]),
            ]);
    }
}
