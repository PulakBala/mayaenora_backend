<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Products\ProductResource;
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
            ->modifyQueryUsing(fn ($query) => $query->with(['items.product.primaryImage', 'items.product.images']))
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

                TextColumn::make('ordered_products')
                    ->label('Ordered Product(s)')
                    ->html()
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('product_name', 'like', "%{$search}%")
                                ->orWhere('variant_details', 'like', "%{$search}%");
                        });
                    })
                    ->state(function (Order $record): string {
                        $items = $record->items;
                        if (! $items || $items->isEmpty()) {
                            return '<span class="text-xs text-gray-400 italic">No products</span>';
                        }

                        $html = '<div class="flex flex-col gap-2 py-1 min-w-[200px] max-w-[320px]">';
                        $frontendBase = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

                        foreach ($items as $item) {
                            $product = $item->product;
                            $adminUrl = $product ? ProductResource::getUrl('edit', ['record' => $product]) : null;
                            $frontendUrl = ($product && $product->slug) ? "{$frontendBase}/products/{$product->slug}" : null;

                            $imgSrc = $product?->primaryImage?->image_url
                                ?? $product?->primaryImage?->image_path
                                ?? $product?->images?->first()?->image_url
                                ?? $product?->images?->first()?->image_path;

                            $imgHtml = '';
                            if ($imgSrc) {
                                $imgUrl = (str_starts_with($imgSrc, 'http://') || str_starts_with($imgSrc, 'https://'))
                                    ? $imgSrc
                                    : asset(str_starts_with($imgSrc, '/') ? ltrim($imgSrc, '/') : 'storage/' . $imgSrc);
                                $imgHtml = '<img src="' . e($imgUrl) . '" alt="" class="w-8 h-8 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0" />';
                            }

                            $productTitle = e($item->product_name ?: ($product?->title ?? 'Product'));
                            $variant = $item->variant_details ? '<span class="text-[11px] text-gray-500 font-normal">(' . e($item->variant_details) . ')</span>' : '';
                            $qty = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">×' . ((int) $item->quantity) . '</span>';

                            $html .= '<div class="flex items-center gap-2 text-xs leading-snug">';
                            if ($imgHtml) {
                                $html .= $imgHtml;
                            }
                            $html .= '<div class="flex-1 min-w-0">';
                            $html .= '<div class="flex items-center gap-1.5 flex-wrap">';
                            $html .= $qty;

                            if ($adminUrl) {
                                $html .= '<a href="' . $adminUrl . '" target="_blank" class="font-semibold text-primary-600 hover:text-primary-800 hover:underline dark:text-primary-400 dark:hover:text-primary-300 transition-colors" title="View / Edit in Admin Panel">' . $productTitle . '</a>';
                            } else {
                                $html .= '<span class="font-semibold text-gray-800 dark:text-gray-200">' . $productTitle . '</span>';
                            }

                            if ($frontendUrl) {
                                $html .= '<a href="' . $frontendUrl . '" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 inline-flex items-center" title="View Live on Store">';
                                $html .= '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>';
                                $html .= '</a>';
                            }

                            $html .= '</div>';
                            if ($variant) {
                                $html .= '<div class="text-[11px] text-gray-500 dark:text-gray-400">' . $variant . '</div>';
                            }
                            $html .= '</div>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';
                        return $html;
                    }),

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
