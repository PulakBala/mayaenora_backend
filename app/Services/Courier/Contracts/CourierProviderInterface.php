<?php

namespace App\Services\Courier\Contracts;

use App\Models\Order;
use App\Services\Courier\DTOs\CourierDispatchResult;
use App\Services\Courier\DTOs\CourierWebhookResult;
use Illuminate\Http\Request;

interface CourierProviderInterface
{
    /**
     * Get the unique provider identifier (e.g. 'steadfast', 'pathao', 'redx').
     */
    public function getProviderName(): string;

    /**
     * Dispatch an order and create consignment in the courier gateway.
     */
    public function dispatch(Order $order, array $options = []): CourierDispatchResult;

    /**
     * Generate the direct tracking URL for the customer.
     */
    public function getTrackingUrl(string $trackingCode): string;

    /**
     * Parse inbound webhook payload from the courier gateway.
     */
    public function handleWebhook(Request $request): CourierWebhookResult;
}

