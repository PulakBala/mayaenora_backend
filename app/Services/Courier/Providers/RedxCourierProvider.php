<?php

namespace App\Services\Courier\Providers;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Services\Courier\Contracts\CourierProviderInterface;
use App\Services\Courier\DTOs\CourierDispatchResult;
use App\Services\Courier\DTOs\CourierWebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RedxCourierProvider implements CourierProviderInterface
{
    public function __construct(
        protected CourierSetting $setting
    ) {}

    public function getProviderName(): string
    {
        return 'redx';
    }

    public function getTrackingUrl(string $trackingCode): string
    {
        return "https://redx.com.bd/track-order?trackingId={$trackingCode}";
    }

    public function dispatch(Order $order, array $options = []): CourierDispatchResult
    {
        $baseUrl = rtrim($this->setting->base_url ?: 'https://openapi.redx.com.bd/v1.0.0-sandbox', '/');
        $endpoint = "{$baseUrl}/parcels";

        $codAmount = isset($options['collection_amount'])
            ? (float) $options['collection_amount']
            : ($order->payment_status === 'paid' ? 0.00 : (float) $order->grand_total);

        $weightGrams = (int) (($options['weight'] ?? 0.5) * 1000);

        if ($this->setting->sandbox_mode && (str_contains(strtolower($this->setting->api_key ?? ''), 'sandbox') || empty($this->setting->api_key))) {
            $mockTracking = 'REDX' . rand(10000000, 99999999);

            return CourierDispatchResult::success(
                provider: $this->getProviderName(),
                consignmentId: $mockTracking,
                trackingCode: $mockTracking,
                trackingUrl: $this->getTrackingUrl($mockTracking),
                message: 'Parcel created successfully (RedX Sandbox Mode).',
                rawResponse: ['tracking_id' => $mockTracking]
            );
        }

        $payload = [
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_area' => $order->shipping_district,
            'customer_address' => $order->shipping_address,
            'merchant_invoice_id' => $order->order_number,
            'cash_collection_amount' => $codAmount,
            'parcel_weight' => $weightGrams,
            'instruction' => $options['notes'] ?? $order->customer_notes ?? '',
        ];

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'API-ACCESS-TOKEN' => 'Bearer ' . $this->setting->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $trackingId = (string) ($data['tracking_id'] ?? uniqid('redx_'));

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $trackingId,
                    trackingCode: $trackingId,
                    trackingUrl: $this->getTrackingUrl($trackingId),
                    message: 'Parcel booked successfully on RedX.',
                    rawResponse: $data
                );
            }

            if ($this->setting->sandbox_mode) {
                $mockTracking = 'REDX' . rand(10000000, 99999999);

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockTracking,
                    trackingCode: $mockTracking,
                    trackingUrl: $this->getTrackingUrl($mockTracking),
                    message: 'Parcel booked via RedX Sandbox fallback: ' . ($response->json()['message'] ?? 'Sandbox simulation'),
                    rawResponse: $response->json() ?? []
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'RedX API Error: ' . ($response->json()['message'] ?? $response->body()),
                rawResponse: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('RedX dispatch exception: ' . $e->getMessage());

            if ($this->setting->sandbox_mode) {
                $mockTracking = 'REDX' . rand(10000000, 99999999);

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockTracking,
                    trackingCode: $mockTracking,
                    trackingUrl: $this->getTrackingUrl($mockTracking),
                    message: 'Parcel simulated in sandbox mode.',
                    rawResponse: ['exception' => $e->getMessage()]
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'Failed to connect to RedX Logistics: ' . $e->getMessage()
            );
        }
    }

    public function handleWebhook(Request $request): CourierWebhookResult
    {
        $payload = $request->all();

        $status = strtolower(trim((string) ($payload['status'] ?? $payload['delivery_status'] ?? '')));
        $trackingId = (string) ($payload['tracking_id'] ?? $payload['trackingId'] ?? '');
        $orderNumber = (string) ($payload['merchant_invoice_id'] ?? $payload['invoice'] ?? '');

        if (! $orderNumber && ! $trackingId) {
            return CourierWebhookResult::failure('Missing order identifiers in RedX payload.', $payload);
        }

        $orderStatus = match ($status) {
            'delivered', 'parcel-delivered' => 'delivered',
            'in-transit', 'dispatched', 'picked-up', 'parcel-dispatched' => 'shipped',
            'out-for-delivery', 'parcel-out-for-delivery' => 'out_for_delivery',
            'cancelled', 'parcel-cancelled' => 'cancelled',
            'returned', 'parcel-returned' => 'returned',
            default => 'shipped',
        };

        $paymentStatus = in_array($status, ['delivered', 'parcel-delivered']) ? 'paid' : null;

        return CourierWebhookResult::success(
            orderNumber: $orderNumber ?: null,
            consignmentId: $trackingId ?: null,
            trackingCode: $trackingId ?: null,
            orderStatus: $orderStatus,
            paymentStatus: $paymentStatus,
            comment: "RedX Logistics event: {$status}",
            rawPayload: $payload
        );
    }
}

