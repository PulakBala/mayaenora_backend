<?php

namespace App\Filament\Resources\PayoutRequests\Tables;

use App\Models\PayoutRequest;
use App\Services\Referral\ReferralService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayoutRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payout_number')
                    ->label('Payout No')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (PayoutRequest $record) => $record->user?->phone ?? $record->user?->email)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color(fn ($state) => match (strtolower((string) $state)) {
                        'bkash' => 'danger',
                        'nagad' => 'warning',
                        'rocket' => 'info',
                        'bank_transfer' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper(str_replace('_', ' ', (string) $state))),

                TextColumn::make('account_number')
                    ->label('Account / Details')
                    ->description(fn (PayoutRequest $record) => $record->bank_name ? "{$record->bank_name} - {$record->branch_name}" : null)
                    ->copyable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),

                TextColumn::make('transaction_reference')
                    ->label('Trx Ref / Reason')
                    ->description(fn (PayoutRequest $record) => $record->rejection_reason)
                    ->placeholder('N/A')
                    ->copyable(),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'paid' => 'Paid / Completed',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('method')
                    ->options([
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
            ])
            ->recordActions([
                // 1. Mark as Paid Action
                Action::make('mark_as_paid')
                    ->label('Mark Paid')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (PayoutRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Payout Disbursement')
                    ->modalDescription('Enter the transaction reference / TrxID from bKash, Nagad or Bank transfer.')
                    ->form([
                        TextInput::make('transaction_reference')
                            ->label('MFS / Bank Transaction Reference (TrxID)')
                            ->placeholder('e.g. 9HJ28S901B')
                            ->required(),

                        Textarea::make('admin_notes')
                            ->label('Optional Processing Note')
                            ->placeholder('e.g. Sent from ELVOA Corporate bKash account'),
                    ])
                    ->action(function (PayoutRequest $record, array $data) {
                        app(ReferralService::class)->approvePayout(
                            $record,
                            auth()->user(),
                            $data['transaction_reference'],
                            $data['admin_notes'] ?? null
                        );

                        Notification::make()
                            ->title('Payout marked as Paid')
                            ->body("Payout #{$record->payout_number} has been successfully settled.")
                            ->success()
                            ->send();
                    }),

                // 2. Reject and Refund Action
                Action::make('reject_payout')
                    ->label('Reject & Refund')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (PayoutRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Payout Request')
                    ->modalDescription('The requested amount will be refunded immediately back to the customer\'s wallet balance.')
                    ->form([
                        TextInput::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->placeholder('e.g. Invalid account number provided / Inactive MFS account')
                            ->required(),
                    ])
                    ->action(function (PayoutRequest $record, array $data) {
                        app(ReferralService::class)->rejectPayout(
                            $record,
                            auth()->user(),
                            $data['rejection_reason']
                        );

                        Notification::make()
                            ->title('Payout Rejected & Refunded')
                            ->body("Payout #{$record->payout_number} rejected. ৳{$record->amount} refunded back to customer wallet.")
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}

