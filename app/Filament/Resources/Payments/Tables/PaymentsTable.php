<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-m-clipboard-document-check'),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('gateway_name')
                    ->label('Gateway / Method')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('fee_deducted')
                    ->label('Gateway Fee')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'info',
                        default => 'danger',
                    }),

                TextColumn::make('created_at')
                    ->label('Processed At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label('Inspect Payload')
                    ->icon('heroicon-m-code-bracket')
                    ->color('gray')
                    ->modalHeading(fn (Payment $record) => "Transaction Audit: #{$record->transaction_id}")
                    ->modalDescription('Full audit log and raw response data received from payment gateway.')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([
                        Grid::make(3)->schema([
                            TextInput::make('transaction_id')
                                ->label('Bank / Gateway Txn ID')
                                ->default(fn (Payment $record) => $record->transaction_id)
                                ->disabled(),

                            TextInput::make('order_number')
                                ->label('Associated Order')
                                ->default(fn (Payment $record) => $record->order?->order_number ?? 'N/A')
                                ->disabled(),

                            TextInput::make('status')
                                ->label('Transaction Status')
                                ->default(fn (Payment $record) => strtoupper($record->status))
                                ->disabled(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('gateway_name')
                                ->label('Gateway Channel')
                                ->default(fn (Payment $record) => $record->gateway_name)
                                ->disabled(),

                            TextInput::make('amount')
                                ->label('Amount (BDT)')
                                ->default(fn (Payment $record) => '৳' . number_format($record->amount, 2))
                                ->disabled(),

                            TextInput::make('fee_deducted')
                                ->label('Fee Deducted (BDT)')
                                ->default(fn (Payment $record) => '৳' . number_format($record->fee_deducted, 2))
                                ->disabled(),
                        ]),

                        Textarea::make('payload_display')
                            ->label('Raw Gateway Callback & Validation Payload (JSON)')
                            ->rows(12)
                            ->default(fn (Payment $record) => is_array($record->payload)
                                ? json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (string) $record->payload)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

