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

class SteadfastCourierProvider implements CourierProviderInterface
{
    public function __construct(
        protected CourierSetting $setting
    ) {}

    public function getProviderName(): string
    {
        return 'steadfast';
    }

    public function getTrackingUrl(string $trackingCode): string
    {
        return "https://steadfast.com.bd/t/{$trackingCode}";
    }

    public function dispatch(Order $order, array $options = []): CourierDispatchResult
    {
        $baseUrl = rtrim($this->setting->base_url ?: 'https://portal.steadfast.com.bd/api/v1', '/');
        $endpoint = "{$baseUrl}/create_order";

        $codAmount = isset($options['collection_amount'])
            ? (float) $options['collection_amount']
            : ($order->payment_status === 'paid' ? 0.00 : (float) $order->grand_total);

        $payload = [
            'invoice' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => $order->shipping_address,
            'cod_amount' => $codAmount,
            'note' => $options['notes'] ?? $order->customer_notes ?? 'Handle with care',
        ];

        // If sandbox mode is active with sandbox/mock keys, provide instant reliable simulation
        if ($this->setting->sandbox_mode && (str_contains(strtolower($this->setting->api_key ?? ''), 'sandbox') || empty($this->setting->api_key))) {
            $mockConsignment = 'SF-' . strtoupper(substr(uniqid(), 7));
            $mockTracking = 'ST' . rand(10000000, 99999999);

            return CourierDispatchResult::success(
                provider: $this->getProviderName(),
                consignmentId: $mockConsignment,
                trackingCode: $mockTracking,
                trackingUrl: $this->getTrackingUrl($mockTracking),
                message: 'Consignment created successfully (Steadfast Sandbox Mode).',
                rawResponse: ['status' => 200, 'consignment' => ['consignment_id' => $mockConsignment, 'tracking_code' => $mockTracking]]
            );
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Api-Key' => $this->setting->api_key,
                    'Secret-Key' => $this->setting->secret_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $consignment = $data['consignment'] ?? [];
                $consignmentId = (string) ($consignment['consignment_id'] ?? $data['consignment_id'] ?? uniqid('sf_'));
                $trackingCode = (string) ($consignment['tracking_code'] ?? $data['tracking_code'] ?? $consignmentId);

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $consignmentId,
                    trackingCode: $trackingCode,
                    trackingUrl: $this->getTrackingUrl($trackingCode),
                    message: $data['message'] ?? 'Consignment created successfully on Steadfast.',
                    rawResponse: $data
                );
            }

            // If in sandbox mode and real API failed, fallback gracefully with simulated tracking
            if ($this->setting->sandbox_mode) {
                $mockConsignment = 'SF-' . strtoupper(substr(uniqid(), 7));
                $mockTracking = 'ST' . rand(10000000, 99999999);

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockConsignment,
                    trackingCode: $mockTracking,
                    trackingUrl: $this->getTrackingUrl($mockTracking),
                    message: 'Consignment created via Sandbox fallback: ' . ($response->json()['message'] ?? 'API responded with non-200'),
                    rawResponse: $response->json() ?? []
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'Steadfast API Error: ' . ($response->json()['message'] ?? $response->body()),
                rawResponse: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Steadfast dispatch exception: ' . $e->getMessage());

            if ($this->setting->sandbox_mode) {
                $mockConsignment = 'SF-' . strtoupper(substr(uniqid(), 7));
                $mockTracking = 'ST' . rand(10000000, 99999999);

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockConsignment,
                    trackingCode: $mockTracking,
                    trackingUrl: $this->getTrackingUrl($mockTracking),
                    message: 'Consignment simulated in sandbox mode.',
                    rawResponse: ['exception' => $e->getMessage()]
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'Failed to connect to Steadfast Courier: ' . $e->getMessage()
            );
        }
    }

    public function handleWebhook(Request $request): CourierWebhookResult
    {
        $payload = $request->all();

        $status = strtolower(trim((string) ($payload['status'] ?? $payload['delivery_status'] ?? '')));
        $consignmentId = (string) ($payload['consignment_id'] ?? $payload['consignmentId'] ?? '');
        $trackingCode = (string) ($payload['tracking_code'] ?? $payload['trackingCode'] ?? '');
        $orderNumber = (string) ($payload['invoice'] ?? $payload['order_number'] ?? '');

        if (! $orderNumber && ! $consignmentId && ! $trackingCode) {
            return CourierWebhookResult::failure('Missing order identifiers in Steadfast payload.', $payload);
        }

        $orderStatus = match ($status) {
            'delivered', 'partial_delivered' => 'delivered',
            'in_transit', 'picked_up' => 'shipped',
            'out_for_delivery' => 'out_for_delivery',
            'cancelled' => 'cancelled',
            'returned' => 'returned',
            'hold', 'in_review' => 'processing',
            default => 'shipped',
        };

        $paymentStatus = in_array($status, ['delivered', 'partial_delivered']) ? 'paid' : null;

        return CourierWebhookResult::success(
            orderNumber: $orderNumber ?: null,
            consignmentId: $consignmentId ?: null,
            trackingCode: $trackingCode ?: null,
            orderStatus: $orderStatus,
            paymentStatus: $paymentStatus,
            comment: "Steadfast Courier status update: {$status}",
            rawPayload: $payload
        );
    }

    /**
     * Query live consignment status from Steadfast API.
     */
    public function syncStatus(Order $order): array
    {
        $baseUrl = rtrim($this->setting->base_url ?: 'https://portal.steadfast.com.bd/api/v1', '/');

        $trackingCode = $order->courier_tracking_code;
        $consignmentId = $order->courier_consignment_id;
        $invoice = $order->order_number;

        if ($trackingCode) {
            $endpoint = "{$baseUrl}/status_by_trackingcode/{$trackingCode}";
        } elseif ($consignmentId) {
            $endpoint = "{$baseUrl}/status_by_cid/{$consignmentId}";
        } else {
            $endpoint = "{$baseUrl}/status_by_invoice/{$invoice}";
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                'Api-Key' => $this->setting->api_key,
                'Secret-Key' => $this->setting->secret_key,
            ])->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $deliveryStatus = strtolower(trim((string) ($data['delivery_status'] ?? $data['status'] ?? '')));

                return [
                    'success' => true,
                    'status' => $deliveryStatus,
                    'data' => $data,
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Steadfast syncStatus error: ' . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Could not retrieve status from Steadfast Courier.'];
    }
}

