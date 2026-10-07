<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Referral\ReferralService;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // When order status is marked as 'delivered', award qualifying referral commission
        if ($order->wasChanged('order_status') && strtolower(trim($order->order_status)) === 'delivered') {
            app(ReferralService::class)->processOrderDelivered($order);
        }
    }
}

