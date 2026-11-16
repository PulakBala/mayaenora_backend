<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
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
            ->query(Order::query()->with(['items.product.primaryImage', 'items.product.images'])->latest()->limit(5))
            ->heading('Recent Orders Placed Online')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('customer_name')
                    ->label('Customer'),

                TextColumn::make('ordered_products')
                    ->label('Ordered Product(s)')
                    ->html()
                    ->state(function (Order $record): string {
                        $items = $record->items;
                        if (! $items || $items->isEmpty()) {
                            return '<span class="text-xs text-gray-400 italic">No products</span>';
                        }

                        $html = '<div class="flex flex-col gap-1.5 py-1 min-w-[180px] max-w-[280px]">';
                        $frontendBase = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

                        foreach ($items as $item) {
                            $product = $item->product;
                            $adminUrl = $product ? ProductResource::getUrl('edit', ['record' => $product]) : null;
                            $frontendUrl = ($product && $product->slug) ? "{$frontendBase}/products/{$product->slug}" : null;

                            $productTitle = e($item->product_name ?: ($product?->title ?? 'Product'));
                            $qty = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">×' . ((int) $item->quantity) . '</span>';

                            $html .= '<div class="flex items-center gap-1.5 text-xs leading-snug">';
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
                        }

                        $html .= '</div>';
                        return $html;
                    }),

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

