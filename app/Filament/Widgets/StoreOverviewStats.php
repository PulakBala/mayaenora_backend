<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todaySales = Order::whereDate('created_at', today())
            ->whereNotIn('order_status', ['cancelled', 'returned'])
            ->sum('grand_total');

        $totalOrders = Order::count();

        $pendingOrders = Order::where('order_status', 'pending')->count();

        $lowStockCount = ProductVariant::whereRaw('stock_quantity <= low_stock_threshold')->count();

        return [
            Stat::make("Today's Sales", '৳' . number_format($todaySales, 2))
                ->description('Real-time gross online revenue today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([500, 1200, 800, 1500, 2200, 1800, $todaySales ?: 1000]),

            Stat::make('Total Orders Placed', number_format($totalOrders))
                ->description('Lifetime order volume')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Pending Action Required', number_format($pendingOrders))
                ->description('Orders awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),

            Stat::make('Low Stock Alerts', number_format($lowStockCount))
                ->description('Variants below reorder threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}

