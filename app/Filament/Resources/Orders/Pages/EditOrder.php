<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\CourierSetting;
use App\Models\OrderStatusHistory;
use App\Services\Courier\CourierManager;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public ?string $previousStatus = null;

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->getOriginal('order_status');

        $courierPartner = $this->data['courier_partner'] ?? null;
        if ($courierPartner === 'delivery' && ($this->data['order_status'] ?? '') !== 'delivered') {
            $this->data['order_status'] = 'delivered';
        } elseif ($courierPartner === 'reject' && ($this->data['order_status'] ?? '') !== 'rejected') {
            $this->data['order_status'] = 'rejected';
        } elseif ($courierPartner === 'closed' && ($this->data['order_status'] ?? '') !== 'closed') {
            $this->data['order_status'] = 'closed';
        }
    }

    protected function afterSave(): void
    {
        if ($this->previousStatus !== $this->record->order_status) {
            OrderStatusHistory::create([
                'order_id' => $this->record->id,
                'status' => $this->record->order_status,
                'comment' => "Status updated from '{$this->previousStatus}' to '{$this->record->order_status}' via Admin Panel.",
                'notified_customer' => false,
                'updated_by' => auth()->id(),
                'created_at' => now(),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dispatch_courier')
                ->label('Dispatch via Courier')
                ->icon('heroicon-m-truck')
                ->color('info')
                ->modalHeading("Dispatch Order #{$this->record->order_number} via Courier")
                ->modalDescription('Create an automated consignment in the courier gateway and transition order to Shipped.')
                ->visible(fn () => in_array($this->record->order_status, ['pending', 'confirmed', 'processing', 'packed']))
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
                        ->default($this->record->courier_partner ?? 'steadfast')
                        ->required(),

                    TextInput::make('collection_amount')
                        ->label('COD Collection Amount (৳)')
                        ->numeric()
                        ->default($this->record->payment_status === 'paid' ? 0.00 : (float) $this->record->grand_total)
                        ->required()
                        ->helperText('Set to 0 if payment was already received.'),

                    TextInput::make('weight')
                        ->label('Parcel Weight (kg)')
                        ->numeric()
                        ->default(0.5)
                        ->required(),

                    Textarea::make('notes')
                        ->label('Delivery Instructions')
                        ->default($this->record->customer_notes ?? 'Handle with care')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $result = CourierManager::dispatchOrder($this->record, $data['courier_partner'], $data);
                    if ($result->success) {
                        Notification::make()
                            ->title('Consignment Created Successfully')
                            ->body("Order dispatched via " . ucfirst($data['courier_partner']) . ". Tracking Code: {$result->trackingCode}")
                            ->success()
                            ->send();

                        $this->refreshFormData(['courier_partner', 'courier_consignment_id', 'courier_tracking_code', 'order_status']);
                    } else {
                        Notification::make()
                            ->title('Courier Dispatch Failed')
                            ->body($result->message)
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('track_courier')
                ->label('Live Tracking')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn () => ! empty($this->record->courier_tracking_code))
                ->url(fn () => match ($this->record->courier_partner) {
                    'steadfast' => "https://steadfast.com.bd/t/{$this->record->courier_tracking_code}",
                    'pathao' => "https://merchant.pathao.com/tracking?consignment_id={$this->record->courier_tracking_code}",
                    'redx' => "https://redx.com.bd/track-order?trackingId={$this->record->courier_tracking_code}",
                    default => null,
                }, shouldOpenInNewTab: true),

            Action::make('sync_courier')
                ->label('Sync Courier Status')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn () => in_array($this->record->courier_partner, ['steadfast', 'pathao', 'redx']) && ! empty($this->record->courier_tracking_code))
                ->action(function () {
                    $res = CourierManager::syncOrderStatus($this->record);
                    if ($res['success']) {
                        Notification::make()
                            ->title('Courier Status Synced')
                            ->body($res['message'])
                            ->success()
                            ->send();

                        $this->refreshFormData(['order_status', 'payment_status']);
                    } else {
                        Notification::make()
                            ->title('Courier Status Sync')
                            ->body($res['message'] ?? 'Could not fetch remote status.')
                            ->info()
                            ->send();
                    }
                }),

            Action::make('verify_payment')
                ->label('Verify Online Payment')
                ->icon('heroicon-m-credit-card')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading("Verify SSLCommerz Payment for Order #{$this->record->order_number}")
                ->modalDescription('Query SSLCommerz validation API to check if payment was settled and update order status to Paid.')
                ->visible(fn () => in_array($this->record->payment_method, ['sslcommerz', 'online']) || $this->record->payment_status === 'pending')
                ->action(function () {
                    /** @var \App\Services\Payment\SSLCommerzService $service */
                    $service = app(\App\Services\Payment\SSLCommerzService::class);
                    $result = $service->verifyOrderPayment($this->record);

                    if ($result['success']) {
                        Notification::make()
                            ->title('Payment Verified')
                            ->body($result['message'])
                            ->success()
                            ->send();

                        $this->refreshFormData(['payment_status', 'payment_method', 'payment_gateway_fee']);
                    } else {
                        Notification::make()
                            ->title('Verification Notice')
                            ->body($result['message'])
                            ->warning()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
