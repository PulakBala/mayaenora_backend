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

class PathaoCourierProvider implements CourierProviderInterface
{
    public function __construct(
        protected CourierSetting $setting
    ) {}

    public function getProviderName(): string
    {
        return 'pathao';
    }

    public function getTrackingUrl(string $trackingCode): string
    {
        return "https://merchant.pathao.com/tracking?consignment_id={$trackingCode}";
    }

    public function dispatch(Order $order, array $options = []): CourierDispatchResult
    {
        $baseUrl = rtrim($this->setting->base_url ?: 'https://courier-api.pathao.com', '/');
        $endpoint = "{$baseUrl}/aladdin/api/v1/orders";

        $codAmount = isset($options['collection_amount'])
            ? (float) $options['collection_amount']
            : ($order->payment_status === 'paid' ? 0.00 : (float) $order->grand_total);

        $storeId = $options['store_id'] ?? $this->setting->additional_settings['store_id'] ?? 1;
        $deliveryType = (int) ($options['delivery_type'] ?? $this->setting->additional_settings['delivery_type'] ?? 48);
        $itemWeight = (float) ($options['weight'] ?? $this->setting->additional_settings['default_weight'] ?? 0.5);

        // Instant simulation for sandbox mode with mock/sandbox credentials
        if ($this->setting->sandbox_mode && (str_contains(strtolower($this->setting->api_key ?? ''), 'sandbox') || empty($this->setting->api_key))) {
            $mockConsignment = 'PT-' . strtoupper(substr(uniqid(), 7));
            $mockTracking = $mockConsignment;

            return CourierDispatchResult::success(
                provider: $this->getProviderName(),
                consignmentId: $mockConsignment,
                trackingCode: $mockTracking,
                trackingUrl: $this->getTrackingUrl($mockTracking),
                message: 'Consignment created successfully (Pathao Sandbox Mode).',
                rawResponse: ['type' => 'success', 'data' => ['consignment_id' => $mockConsignment]]
            );
        }

        $payload = [
            'store_id' => (int) $storeId,
            'merchant_order_id' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => $order->shipping_address,
            'recipient_city' => 1, // Default Dhaka city ID
            'recipient_zone' => 1,
            'delivery_type' => $deliveryType,
            'item_type' => 2, // Parcel
            'special_instruction' => $options['notes'] ?? $order->customer_notes ?? '',
            'item_quantity' => (int) ($order->items()->sum('quantity') ?: 1),
            'item_weight' => $itemWeight,
            'amount_to_collect' => $codAmount,
            'item_description' => 'Fabri Store Order #' . $order->order_number,
        ];

        try {
            $token = $this->setting->api_key;
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $consignmentId = (string) ($data['data']['consignment_id'] ?? $data['consignment_id'] ?? uniqid('pt_'));

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $consignmentId,
                    trackingCode: $consignmentId,
                    trackingUrl: $this->getTrackingUrl($consignmentId),
                    message: $data['message'] ?? 'Consignment created successfully on Pathao.',
                    rawResponse: $data
                );
            }

            if ($this->setting->sandbox_mode) {
                $mockConsignment = 'PT-' . strtoupper(substr(uniqid(), 7));

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockConsignment,
                    trackingCode: $mockConsignment,
                    trackingUrl: $this->getTrackingUrl($mockConsignment),
                    message: 'Consignment created via Sandbox fallback: ' . ($response->json()['message'] ?? 'Sandbox simulation'),
                    rawResponse: $response->json() ?? []
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'Pathao API Error: ' . ($response->json()['message'] ?? $response->body()),
                rawResponse: $response->json() ?? []
            );
        } catch (\Throwable $e) {
            Log::error('Pathao dispatch exception: ' . $e->getMessage());

            if ($this->setting->sandbox_mode) {
                $mockConsignment = 'PT-' . strtoupper(substr(uniqid(), 7));

                return CourierDispatchResult::success(
                    provider: $this->getProviderName(),
                    consignmentId: $mockConsignment,
                    trackingCode: $mockConsignment,
                    trackingUrl: $this->getTrackingUrl($mockConsignment),
                    message: 'Consignment simulated in sandbox mode.',
                    rawResponse: ['exception' => $e->getMessage()]
                );
            }

            return CourierDispatchResult::failure(
                provider: $this->getProviderName(),
                message: 'Failed to connect to Pathao Courier: ' . $e->getMessage()
            );
        }
    }

    public function handleWebhook(Request $request): CourierWebhookResult
    {
        $payload = $request->all();

        $status = strtolower(trim((string) ($payload['event'] ?? $payload['order_status'] ?? $payload['status'] ?? '')));
        $consignmentId = (string) ($payload['consignment_id'] ?? '');
        $orderNumber = (string) ($payload['merchant_order_id'] ?? '');

        if (! $orderNumber && ! $consignmentId) {
            return CourierWebhookResult::failure('Missing order identifiers in Pathao payload.', $payload);
        }

        $orderStatus = match ($status) {
            'order_delivered', 'delivered' => 'delivered',
            'in_transit', 'picked_up', 'assigned_for_pickup' => 'shipped',
            'out_for_delivery' => 'out_for_delivery',
            'order_cancelled', 'cancelled' => 'cancelled',
            'returned', 'return_received' => 'returned',
            default => 'shipped',
        };

        $paymentStatus = in_array($status, ['order_delivered', 'delivered']) ? 'paid' : null;

        return CourierWebhookResult::success(
            orderNumber: $orderNumber ?: null,
            consignmentId: $consignmentId ?: null,
            trackingCode: $consignmentId ?: null,
            orderStatus: $orderStatus,
            paymentStatus: $paymentStatus,
            comment: "Pathao Courier event: {$status}",
            rawPayload: $payload
        );
    }
}

