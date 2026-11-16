<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer & Delivery Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('order_number')
                                ->label('Order Reference #')
                                ->disabled(),

                            TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->required(),

                            TextInput::make('customer_phone')
                                ->label('Contact Phone')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('customer_email')
                                ->label('Email Address')
                                ->email(),

                            TextInput::make('shipping_district')
                                ->label('District')
                                ->required(),
                        ]),

                        Textarea::make('shipping_address')
                            ->label('Delivery Street Address')
                            ->required()
                            ->rows(2),

                        TextInput::make('customer_notes')
                            ->label('Customer Delivery Instructions')
                            ->columnSpanFull(),
                    ]),

                Section::make('Order Fulfillment & Courier Dispatch')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('order_status')
                                ->label('Fulfillment Status')
                                ->options([
                                    'pending' => 'Pending Confirmation',
                                    'confirmed' => 'Confirmed',
                                    'processing' => 'Processing',
                                    'packed' => 'Packed',
                                    'shipped' => 'Shipped / In Transit',
                                    'out_for_delivery' => 'Out For Delivery',
                                    'delivered' => 'Delivered (Delivery Completed)',
                                    'rejected' => 'Rejected (Customer Refused / Reject)',
                                    'cancelled' => 'Cancelled',
                                    'returned' => 'Returned',
                                    'closed' => 'Closed / Completed',
                                ])
                                ->required()
                                ->live(),

                            Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'partially_paid' => 'Partially Paid',
                                    'refunded' => 'Refunded',
                                    'failed' => 'Failed',
                                ])
                                ->required(),

                            Select::make('payment_method')
                                ->label('Payment Channel')
                                ->options([
                                    'cod' => 'Cash on Delivery (COD)',
                                    'bkash' => 'bKash Mobile Banking',
                                    'nagad' => 'Nagad Mobile Banking',
                                    'card' => 'Credit / Debit Card',
                                    'sslcommerz' => 'SSLCommerz Gateway',
                                ])
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            Select::make('courier_partner')
                                ->label('Courier Partner')
                                ->options([
                                    'steadfast' => 'Steadfast Courier',
                                    'pathao' => 'Pathao Courier',
                                    'redx' => 'RedX Logistics',
                                    'internal' => 'Internal Fleet',
                                    'delivery' => 'Delivery',
                                    'reject' => 'Reject',
                                    'closed' => 'Closed',
                                ])
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state === 'delivery') {
                                        $set('order_status', 'delivered');
                                    } elseif ($state === 'reject') {
                                        $set('order_status', 'rejected');
                                    } elseif ($state === 'closed') {
                                        $set('order_status', 'closed');
                                    }
                                }),

                            TextInput::make('courier_tracking_code')
                                ->label('Consignment Tracking Code / Link'),

                            TextInput::make('courier_consignment_id')
                                ->label('Partner Consignment ID'),
                        ]),
                    ]),

                Section::make('Order Line Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('Product Title')
                                    ->required()
                                    ->columnSpan(2)
                                    ->suffixActions([
                                        Action::make('open_product_admin')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->tooltip('Open Product Details in Admin')
                                            ->url(fn ($record) => $record?->product ? ProductResource::getUrl('edit', ['record' => $record->product]) : null, shouldOpenInNewTab: true)
                                            ->visible(fn ($record) => (bool) ($record?->product_id)),
                                        Action::make('open_product_store')
                                            ->icon('heroicon-m-globe-alt')
                                            ->color('gray')
                                            ->tooltip('View Live on Customer Store')
                                            ->url(function ($record) {
                                                $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');
                                                return ($record?->product && $record->product->slug) ? "{$frontend}/products/{$record->product->slug}" : null;
                                            }, shouldOpenInNewTab: true)
                                            ->visible(fn ($record) => (bool) ($record?->product?->slug)),
                                    ]),

                                TextInput::make('variant_details')
                                    ->label('Variant (Size/Color)'),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('unit_sale_price')
                                    ->label('Price (৳)')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('subtotal')
                                    ->label('Subtotal (৳)')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(6)
                            ->columnSpanFull()
                            ->addable(false)
                            ->deletable(false),
                    ]),

                Section::make('Financials & P&L Analysis')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('subtotal_amount')
                                ->label('Items Subtotal')
                                ->numeric()
                                ->prefix('৳')
                                ->required(),

                            TextInput::make('discount_amount')
                                ->label('Coupon Discount')
                                ->numeric()
                                ->prefix('৳')
                                ->default(0.00),

                            TextInput::make('delivery_charge')
                                ->label('Delivery Fee')
                                ->numeric()
                                ->prefix('৳')
                                ->required(),

                            TextInput::make('grand_total')
                                ->label('Invoice Grand Total')
                                ->numeric()
                                ->prefix('৳')
                                ->required(),
                        ]),

                        Grid::make(4)->schema([
                            TextInput::make('total_product_cost')
                                ->label('Product COGS')
                                ->numeric()
                                ->prefix('৳'),

                            TextInput::make('packaging_cost')
                                ->label('Packaging Cost')
                                ->numeric()
                                ->prefix('৳'),

                            TextInput::make('actual_courier_cost')
                                ->label('Actual Courier Bill')
                                ->numeric()
                                ->prefix('৳'),

                            TextInput::make('net_profit')
                                ->label('Order Net Profit')
                                ->numeric()
                                ->prefix('৳'),
                        ]),
                    ]),
            ]);
    }
}
