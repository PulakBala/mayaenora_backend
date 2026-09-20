<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProfitLossWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $validOrders = Order::whereNotIn('order_status', ['cancelled', 'returned']);

        $grossProfit = (float) $validOrders->sum(DB::raw('subtotal_amount - total_product_cost'));
        $courierCosts = (float) $validOrders->sum('actual_courier_cost');
        $businessExpenses = (float) Expense::sum('amount');
        $orderNetProfit = (float) $validOrders->sum('net_profit');
        $realizedNetProfit = $orderNetProfit - $businessExpenses;

        return [
            Stat::make('Gross Merchandise Profit', '৳' . number_format($grossProfit, 2))
                ->description('Subtotal revenue minus cost of goods sold')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Delivery & Logistics Cost', '৳' . number_format($courierCosts, 2))
                ->description('Actual courier charges incurred')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Total Business Overhead', '৳' . number_format($businessExpenses, 2))
                ->description('Operational expenses from ledger vouchers')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Net Realized Operating Profit', '৳' . number_format($realizedNetProfit, 2))
                ->description('Gross profit minus operating costs and overhead')
                ->descriptionIcon('heroicon-m-scale')
                ->color($realizedNetProfit >= 0 ? 'success' : 'danger'),
        ];
    }
}

