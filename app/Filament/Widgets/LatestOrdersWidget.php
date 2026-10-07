<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest()->limit(5))
            ->heading('Recent Orders Placed Online')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('Customer'),

                TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->icon('heroicon-m-phone'),

                TextColumn::make('shipping_district')
                    ->badge()
                    ->color('gray')
                    ->label('District'),

                TextColumn::make('grand_total')
                    ->money('BDT')
                    ->weight('bold')
                    ->label('Total'),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    })
                    ->label('Payment'),

                TextColumn::make('order_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'closed' => 'info',
                        'shipped', 'out_for_delivery' => 'info',
                        'processing', 'confirmed', 'packed' => 'primary',
                        'pending' => 'warning',
                        'cancelled', 'rejected', 'returned' => 'danger',
                        default => 'gray',
                    })
                    ->label('Fulfillment'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, h:i A')
                    ->label('Placed At'),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Open Order')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

