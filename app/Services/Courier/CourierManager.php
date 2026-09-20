<?php

namespace App\Services\Courier;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Courier\Contracts\CourierProviderInterface;
use App\Services\Courier\DTOs\CourierDispatchResult;
use App\Services\Courier\Providers\PathaoCourierProvider;
use App\Services\Courier\Providers\RedxCourierProvider;
use App\Services\Courier\Providers\SteadfastCourierProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CourierManager
{
    /**
     * Get all active courier providers.
     */
    public static function getActiveProviders(): Collection
    {
        return CourierSetting::active()->get();
    }

    /**
     * Resolve courier provider instance by identifier.
     */
    public static function provider(string $provider): CourierProviderInterface
    {
        $setting = CourierSetting::where('provider', strtolower($provider))->first();

        if (! $setting) {
            throw new InvalidArgumentException("Courier provider [{$provider}] is not configured in the database.");
        }

        return match (strtolower($provider)) {
            'steadfast' => new SteadfastCourierProvider($setting),
            'pathao' => new PathaoCourierProvider($setting),
            'redx' => new RedxCourierProvider($setting),
            default => throw new InvalidArgumentException("Unsupported courier provider [{$provider}]."),
        };
    }

    /**
     * Dispatch an order through the specified courier gateway.
     */
    public static function dispatchOrder(Order $order, string $providerName, array $options = []): CourierDispatchResult
    {
        $provider = static::provider($providerName);
        $result = $provider->dispatch($order, $options);

        if ($result->success) {
            $previousStatus = $order->order_status;

            $order->update([
                'courier_partner' => strtolower($providerName),
                'courier_consignment_id' => $result->consignmentId,
                'courier_tracking_code' => $result->trackingCode,
                'order_status' => 'shipped',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'shipped',
                'comment' => "Order dispatched via " . ucfirst($providerName) . " (Tracking: {$result->trackingCode}, Consignment: {$result->consignmentId}). Previous status: {$previousStatus}.",
                'notified_customer' => false,
                'updated_by' => auth()->id(),
                'created_at' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Handle incoming webhook for a given provider.
     */
    public static function handleWebhook(string $providerName, Request $request): bool
    {
        $provider = static::provider($providerName);
        $result = $provider->handleWebhook($request);

        if (! $result->success) {
            return false;
        }

        // Locate order by tracking code, consignment id, or order number
        $order = null;

        if ($result->orderNumber) {
            $order = Order::where('order_number', $result->orderNumber)->first();
        }

        if (! $order && $result->trackingCode) {
            $order = Order::where('courier_tracking_code', $result->trackingCode)->first();
        }

        if (! $order && $result->consignmentId) {
            $order = Order::where('courier_consignment_id', $result->consignmentId)->first();
        }

        if (! $order) {
            return false;
        }

        $updates = [];
        if ($result->orderStatus && $order->order_status !== $result->orderStatus) {
            $updates['order_status'] = $result->orderStatus;
        }

        if ($result->paymentStatus && $order->payment_status !== $result->paymentStatus) {
            $updates['payment_status'] = $result->paymentStatus;
        }

        if (! empty($updates)) {
            $order->update($updates);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->order_status,
                'comment' => $result->comment ?: "Updated via {$providerName} webhook.",
                'notified_customer' => false,
                'updated_by' => null,
                'created_at' => now(),
            ]);
        }

        return true;
    }
}

