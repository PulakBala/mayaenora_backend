<?php

namespace App\Filament\Resources\PayoutRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayoutRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout Request Details')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('payout_number')
                                ->label('Payout Tracking No')
                                ->disabled(),

                            TextInput::make('amount')
                                ->label('Requested Amount')
                                ->numeric()
                                ->prefix('৳')
                                ->disabled(),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending Review',
                                    'paid' => 'Paid / Completed',
                                    'rejected' => 'Rejected (Refunded to Wallet)',
                                ])
                                ->disabled(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('method')
                                ->label('Payout Method')
                                ->disabled(),

                            TextInput::make('account_number')
                                ->label('Target Account Number')
                                ->disabled(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('account_name')
                                ->label('Account Name')
                                ->disabled(),

                            TextInput::make('bank_name')
                                ->label('Bank Name')
                                ->disabled(),

                            TextInput::make('branch_name')
                                ->label('Branch Name')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Disbursement & Processing Info')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('transaction_reference')
                                ->label('Bank / MFS Transaction ID (TrxID)')
                                ->placeholder('e.g. bKash TrxID #8AFK1289')
                                ->maxLength(150),

                            TextInput::make('rejection_reason')
                                ->label('Rejection Reason (if rejected)')
                                ->maxLength(255),
                        ]),

                        Textarea::make('admin_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

