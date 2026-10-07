<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Services\Courier\CourierManager;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-m-phone'),

                TextColumn::make('shipping_district')
                    ->label('District')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'info',
                        default => 'danger',
                    }),

                TextColumn::make('order_status')
                    ->label('Fulfillment')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivered' => 'Delivered',
                        'rejected' => 'Rejected',
                        'closed' => 'Closed',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned',
                        'shipped' => 'Shipped',
                        'out_for_delivery' => 'Out For Delivery',
                        'processing' => 'Processing',
                        'confirmed' => 'Confirmed',
                        'packed' => 'Packed',
                        'pending' => 'Pending',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'closed' => 'info',
                        'shipped', 'out_for_delivery' => 'info',
                        'processing', 'confirmed', 'packed' => 'primary',
                        'pending' => 'warning',
                        'cancelled', 'rejected', 'returned' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('courier_partner')
                    ->label('Courier')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'steadfast' => 'Steadfast',
                        'pathao' => 'Pathao',
                        'redx' => 'RedX',
                        'internal' => 'Internal Fleet',
                        'delivery' => 'Delivery',
                        'reject' => 'Reject',
                        'closed' => 'Closed',
                        default => ucfirst($state ?? 'Unassigned'),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'delivery' => 'success',
                        'reject' => 'danger',
                        'closed' => 'info',
                        'steadfast', 'pathao', 'redx' => 'primary',
                        default => 'gray',
                    })
                    ->placeholder('Unassigned'),

                TextColumn::make('courier_tracking_code')
                    ->label('Tracking #')
                    ->searchable()
                    ->copyable()
                    ->placeholder('None'),

                TextColumn::make('created_at')
                    ->label('Placed At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('order_status')
                    ->label('Fulfillment Status')
                    ->options([
                        'pending' => 'Pending Confirmation',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'packed' => 'Packed',
                        'shipped' => 'Shipped / In Transit',
                        'out_for_delivery' => 'Out For Delivery',
                        'delivered' => 'Delivered (Delivery)',
                        'rejected' => 'Rejected (Reject)',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned',
                        'closed' => 'Closed',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('courier_partner')
                    ->label('Courier Partner')
                    ->options([
                        'steadfast' => 'Steadfast Courier',
                        'pathao' => 'Pathao Courier',
                        'redx' => 'RedX Logistics',
                        'internal' => 'Internal Fleet',
                        'delivery' => 'Delivery',
                        'reject' => 'Reject',
                        'closed' => 'Closed',
                    ]),
            ])
            ->recordActions([
                Action::make('dispatch_courier')
                    ->label('Dispatch')
                    ->icon('heroicon-m-truck')
                    ->color('info')
                    ->modalHeading(fn (Order $record) => "Dispatch Order #{$record->order_number} via Courier")
                    ->modalDescription('Create an automated consignment in the courier gateway and transition order to Shipped.')
                    ->visible(fn (Order $record) => in_array($record->order_status, ['pending', 'confirmed', 'processing', 'packed']))
                    ->form([
                        Select::make('courier_partner')
                            ->label('Select Active Courier')
                            ->options(function () {
                                $active = CourierSetting::where('is_active', true)->pluck('name', 'provider')->toArray();

                                return ! empty($active) ? $active : [
                                    'steadfast' => 'Steadfast Courier (Default)',
                                    'pathao' => 'Pathao Courier',
                                    'redx' => 'RedX Logistics',
                                ];
                            })
                            ->default(fn (Order $record) => $record->courier_partner ?? 'steadfast')
                            ->required(),

                        TextInput::make('collection_amount')
                            ->label('COD Collection Amount (৳)')
                            ->numeric()
                            ->default(fn (Order $record) => $record->payment_status === 'paid' ? 0.00 : (float) $record->grand_total)
                            ->required()
                            ->helperText('Set to 0 if payment was already received.'),

                        TextInput::make('weight')
                            ->label('Parcel Weight (kg)')
                            ->numeric()
                            ->default(0.5)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Delivery Instructions')
                            ->default(fn (Order $record) => $record->customer_notes ?? 'Handle with care')
                            ->rows(2),
                    ])
                    ->action(function (Order $record, array $data) {
                        $result = CourierManager::dispatchOrder($record, $data['courier_partner'], $data);
                        if ($result->success) {
                            Notification::make()
                                ->title('Consignment Created Successfully')
                                ->body("Order #{$record->order_number} dispatched via " . ucfirst($data['courier_partner']) . ". Tracking Code: {$result->trackingCode}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Courier Dispatch Failed')
                                ->body($result->message)
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('track_courier')
                    ->label('Track')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Order $record) => ! empty($record->courier_tracking_code))
                    ->url(fn (Order $record) => match ($record->courier_partner) {
                        'steadfast' => "https://steadfast.com.bd/t/{$record->courier_tracking_code}",
                        'pathao' => "https://merchant.pathao.com/tracking?consignment_id={$record->courier_tracking_code}",
                        'redx' => "https://redx.com.bd/track-order?trackingId={$record->courier_tracking_code}",
                        default => null,
                    }, shouldOpenInNewTab: true),

                Action::make('sync_courier')
                    ->label('Sync Status')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->visible(fn (Order $record) => in_array($record->courier_partner, ['steadfast', 'pathao', 'redx']) && ! empty($record->courier_tracking_code))
                    ->action(function (Order $record) {
                        $res = CourierManager::syncOrderStatus($record);
                        if ($res['success']) {
                            Notification::make()
                                ->title('Courier Synced')
                                ->body($res['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Courier Sync')
                                ->body($res['message'] ?? 'Could not fetch remote status.')
                                ->info()
                                ->send();
                        }
                    }),

                Action::make('verify_payment')
                    ->label('Verify Payment')
                    ->icon('heroicon-m-credit-card')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Order $record) => "Verify SSLCommerz Payment for Order #{$record->order_number}")
                    ->modalDescription('Query SSLCommerz validation API to check if payment was settled and update order status to Paid.')
                    ->visible(fn (Order $record) => in_array($record->payment_method, ['sslcommerz', 'online']) || $record->payment_status === 'pending')
                    ->action(function (Order $record) {
                        /** @var \App\Services\Payment\SSLCommerzService $service */
                        $service = app(\App\Services\Payment\SSLCommerzService::class);
                        $result = $service->verifyOrderPayment($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Payment Verified')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Verification Notice')
                                ->body($result['message'])
                                ->warning()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
