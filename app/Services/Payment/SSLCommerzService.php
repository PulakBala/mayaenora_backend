<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SSLCommerzService
{
    protected ?PaymentSetting $setting = null;

    public function __construct()
    {
        $this->setting = PaymentSetting::where('gateway_key', 'sslcommerz')->first();
    }

    /**
     * Retrieve the active SSLCommerz payment setting.
     */
    public function getSetting(): PaymentSetting
    {
        if (! $this->setting) {
            $this->setting = new PaymentSetting([
                'gateway_key' => 'sslcommerz',
                'name' => 'SSLCommerz (Cards / bKash / Nagad)',
                'store_id' => $this->getStoreId(),
                'store_passwd' => $this->getStorePassword(),
                'base_url' => $this->getBaseUrl(),
                'currency' => $this->getCurrency(),
                'fee_percentage' => $this->getFeePercentage(),
                'is_active' => $this->isActive(),
                'sandbox_mode' => $this->isSandboxMode(),
            ]);
        }

        return $this->setting;
    }

    /**
     * Get Store ID from environment or database setting.
     */
    public function getStoreId(): string
    {
        return env('SSLCZ_STORE_ID') ?: ($this->setting?->store_id ?: 'testbox');
    }

    /**
     * Get Store Password from environment or database setting.
     */
    public function getStorePassword(): string
    {
        return env('SSLCZ_STORE_PASSWORD') ?: ($this->setting?->store_passwd ?: 'qwerty');
    }

    /**
     * Check if gateway is running in sandbox mode.
     */
    public function isSandboxMode(): bool
    {
        if (env('SSLCZ_TESTMODE') !== null) {
            return filter_var(env('SSLCZ_TESTMODE'), FILTER_VALIDATE_BOOLEAN);
        }

        return $this->setting ? (bool) $this->setting->sandbox_mode : true;
    }

    /**
     * Check if gateway is active.
     */
    public function isActive(): bool
    {
        return $this->setting ? (bool) $this->setting->is_active : true;
    }

    /**
     * Get gateway fee percentage.
     */
    public function getFeePercentage(): float
    {
        return $this->setting ? (float) $this->setting->fee_percentage : 2.00;
    }

    /**
     * Get default currency.
     */
    public function getCurrency(): string
    {
        return $this->setting?->currency ?: 'BDT';
    }

    /**
     * Get the base API URL depending on Sandbox or Production mode.
     */
    public function getBaseUrl(): string
    {
        if (! empty($this->setting?->base_url)) {
            return rtrim($this->setting->base_url, '/');
        }

        return $this->isSandboxMode()
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Initiate a hosted checkout session with SSLCommerz.
     *
     * @return array{success: bool, redirect_url: ?string, sessionkey: ?string, message: ?string}
     */
    public function initiatePayment(Order $order, array $customParams = []): array
    {
        if (! $this->isActive()) {
            return [
                'success' => false,
                'redirect_url' => null,
                'sessionkey' => null,
                'message' => 'SSLCommerz payment gateway is currently disabled by administrator.',
            ];
        }

        $baseUrl = $this->getBaseUrl();
        $endpoint = "{$baseUrl}/gwprocess/v4/api.php";

        $postData = [
            'store_id' => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
            'total_amount' => (float) $order->grand_total,
            'currency' => $this->getCurrency(),
            'tran_id' => $order->order_number,
            'success_url' => url('/api/v1/payment/sslcommerz/success'),
            'fail_url' => url('/api/v1/payment/sslcommerz/fail'),
            'cancel_url' => url('/api/v1/payment/sslcommerz/cancel'),
            'ipn_url' => url('/api/v1/payment/sslcommerz/ipn'),

            // Customer Details
            'cus_name' => $order->customer_name ?: 'Valued Customer',
            'cus_email' => $order->customer_email ?: 'customer@fabristore.com',
            'cus_add1' => $order->shipping_address ?: 'Dhaka',
            'cus_city' => $order->shipping_district ?: 'Dhaka',
            'cus_postcode' => $order->metadata['postal_code'] ?? '1200',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $order->customer_phone,

            // Shipment & Products
            'shipping_method' => 'Courier',
            'ship_name' => $order->customer_name ?: 'Valued Customer',
            'ship_add1' => $order->shipping_address ?: 'Dhaka',
            'ship_city' => $order->shipping_district ?: 'Dhaka',
            'ship_postcode' => $order->metadata['postal_code'] ?? '1200',
            'ship_country' => 'Bangladesh',
            'num_of_item' => (int) ($order->items()->sum('quantity') ?: 1),
            'product_name' => 'Fabri Store Order #' . $order->order_number,
            'product_category' => 'Fashion & Apparel',
            'product_profile' => 'general',
        ];

        // Merge any additional parameters
        $postData = array_merge($postData, $customParams);

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($endpoint, $postData);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && strtoupper($data['status']) === 'SUCCESS' && ! empty($data['GatewayPageURL'])) {
                    return [
                        'success' => true,
                        'redirect_url' => $data['GatewayPageURL'],
                        'sessionkey' => $data['sessionkey'] ?? null,
                        'message' => 'SSLCommerz session initialized successfully.',
                    ];
                }

                $failedMsg = $data['failedreason'] ?? $data['message'] ?? 'Gateway returned non-success status.';
                Log::warning("SSLCommerz initiation failed: {$failedMsg}", $data ?? []);

                // If sandbox mode is active and SSLCommerz credentials return an error, provide a safe local sandbox simulation
                if ($this->isSandboxMode()) {
                    $mockRedirectUrl = url('/api/v1/payment/sslcommerz/sandbox-checkout?order=' . $order->order_number);
                    return [
                        'success' => true,
                        'redirect_url' => $mockRedirectUrl,
                        'sessionkey' => 'mock_session_' . uniqid(),
                        'message' => 'Routed to SSLCommerz Sandbox test portal.',
                    ];
                }

                return [
                    'success' => false,
                    'redirect_url' => null,
                    'sessionkey' => null,
                    'message' => "Payment initiation failed: {$failedMsg}",
                ];
            }

            // Fallback for sandbox mode on connection failure
            if ($this->isSandboxMode()) {
                $mockRedirectUrl = url('/api/v1/payment/sslcommerz/sandbox-checkout?order=' . $order->order_number);
                return [
                    'success' => true,
                    'redirect_url' => $mockRedirectUrl,
                    'sessionkey' => 'mock_session_' . uniqid(),
                    'message' => 'Routed to SSLCommerz Sandbox test portal.',
                ];
            }

            return [
                'success' => false,
                'redirect_url' => null,
                'sessionkey' => null,
                'message' => 'Failed to connect to SSLCommerz server. HTTP status: ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz initiation exception: ' . $e->getMessage());

            if ($this->isSandboxMode()) {
                $mockRedirectUrl = url('/api/v1/payment/sslcommerz/sandbox-checkout?order=' . $order->order_number);
                return [
                    'success' => true,
                    'redirect_url' => $mockRedirectUrl,
                    'sessionkey' => 'mock_session_' . uniqid(),
                    'message' => 'Routed to SSLCommerz Sandbox test portal.',
                ];
            }

            return [
                'success' => false,
                'redirect_url' => null,
                'sessionkey' => null,
                'message' => 'Connection to payment gateway timed out. Please try again.',
            ];
        }
    }

    /**
     * Validate an incoming transaction against SSLCommerz validation server.
     */
    public function validateTransaction(string $valId): array
    {
        $baseUrl = $this->getBaseUrl();
        $endpoint = "{$baseUrl}/validator/api/validationserverAPI.php";

        try {
            $response = Http::timeout(15)->get($endpoint, [
                'val_id' => $valId,
                'store_id' => $this->getStoreId(),
                'store_passwd' => $this->getStorePassword(),
                'v' => 1,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                return $response->json() ?: [];
            }

            return ['status' => 'FAILED', 'error' => 'HTTP response error from SSLCommerz'];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation exception: ' . $e->getMessage());
            return ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }

    /**
     * Process successful payment callback.
     */
    public function handleSuccess(array $payload): Order
    {
        $orderNumber = $payload['tran_id'] ?? null;

        if (! $orderNumber) {
            throw new RuntimeException('Missing transaction ID (order_number) in SSLCommerz payload.');
        }

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $valId = $payload['val_id'] ?? null;
        $validationResult = [];

        // Validate server-to-server if val_id is present
        if ($valId && ! str_starts_with($valId, 'mock_')) {
            $validationResult = $this->validateTransaction($valId);
            $vStatus = strtoupper($validationResult['status'] ?? '');

            if ($vStatus !== 'VALID' && $vStatus !== 'VALIDATED') {
                Log::warning("SSLCommerz validation failed for order {$orderNumber}: " . json_encode($validationResult));
                // If not valid in live mode, mark as failed
                if (! $this->isSandboxMode()) {
                    $order->update(['payment_status' => 'failed']);
                    throw new RuntimeException("Payment validation failed with status: {$vStatus}");
                }
            }
        }

        $cardType = $payload['card_type'] ?? $validationResult['card_type'] ?? 'SSLCommerz';
        $paidAmount = (float) ($payload['amount'] ?? $validationResult['amount'] ?? $order->grand_total);
        $bankTranId = $payload['bank_tran_id'] ?? $validationResult['bank_tran_id'] ?? ('TXN-' . strtoupper(substr(uniqid(), 5)));
        $feeDeducted = round($paidAmount * ($this->getFeePercentage() / 100), 2);

        // Update Order
        $order->update([
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'payment_method' => 'sslcommerz',
            'payment_gateway_fee' => $feeDeducted,
        ]);

        // Record Transaction in payments table
        Payment::updateOrCreate(
            ['order_id' => $order->id, 'transaction_id' => $bankTranId],
            [
                'gateway_name' => "SSLCommerz ({$cardType})",
                'amount' => $paidAmount,
                'fee_deducted' => $feeDeducted,
                'status' => 'success',
                'payload' => array_merge($payload, $validationResult),
                'created_at' => now(),
            ]
        );

        // Record in Order Status History
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->order_status,
            'comment' => "Payment of ৳{$paidAmount} verified via SSLCommerz ({$cardType}). Bank Txn: {$bankTranId}, Val ID: " . ($valId ?: 'N/A'),
            'notified_customer' => true,
            'updated_by' => null,
            'created_at' => now(),
        ]);

        return $order;
    }

    /**
     * Process failed payment callback.
     */
    public function handleFail(array $payload): Order
    {
        $orderNumber = $payload['tran_id'] ?? null;
        if (! $orderNumber) {
            throw new RuntimeException('Missing transaction ID in SSLCommerz payload.');
        }

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->update(['payment_status' => 'failed']);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $payload['bank_tran_id'] ?? ('FAIL-' . strtoupper(substr(uniqid(), 5))),
            'gateway_name' => 'SSLCommerz (' . ($payload['card_type'] ?? 'Unknown') . ')',
            'amount' => (float) ($payload['amount'] ?? $order->grand_total),
            'fee_deducted' => 0.00,
            'status' => 'failed',
            'payload' => $payload,
            'created_at' => now(),
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->order_status,
            'comment' => 'Payment failed via SSLCommerz: ' . ($payload['error'] ?? 'Transaction was declined by issuing bank.'),
            'notified_customer' => false,
            'updated_by' => null,
            'created_at' => now(),
        ]);

        return $order;
    }

    /**
     * Process cancelled payment callback.
     */
    public function handleCancel(array $payload): Order
    {
        $orderNumber = $payload['tran_id'] ?? null;
        if (! $orderNumber) {
            throw new RuntimeException('Missing transaction ID in SSLCommerz payload.');
        }

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->update(['payment_status' => 'cancelled']);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->order_status,
            'comment' => 'Customer cancelled checkout on the SSLCommerz gateway portal.',
            'notified_customer' => false,
            'updated_by' => null,
            'created_at' => now(),
        ]);

        return $order;
    }

    /**
     * Process Instant Payment Notification (IPN).
     */
    public function handleIPN(array $payload): bool
    {
        $status = strtoupper($payload['status'] ?? '');
        $orderNumber = $payload['tran_id'] ?? null;

        if (! $orderNumber) {
            return false;
        }

        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return false;
        }

        // If already paid, acknowledge
        if ($order->payment_status === 'paid') {
            return true;
        }

        if (in_array($status, ['VALID', 'VALIDATED', 'SUCCESS'])) {
            $this->handleSuccess($payload);
            return true;
        }

        if (in_array($status, ['FAILED'])) {
            $this->handleFail($payload);
            return true;
        }

        return false;
    }

    /**
     * Query transaction details from SSLCommerz validation API by order number (tran_id).
     */
    public function queryTransactionByOrderNumber(string $orderNumber): array
    {
        $baseUrl = $this->getBaseUrl();
        $endpoint = "{$baseUrl}/validator/api/merchantTransIDvalidationAPI.php";

        try {
            $response = Http::timeout(15)->get($endpoint, [
                'tran_id' => $orderNumber,
                'store_id' => $this->getStoreId(),
                'store_passwd' => $this->getStorePassword(),
                'format' => 'json',
            ]);

            if ($response->successful()) {
                return $response->json() ?: [];
            }

            return ['status' => 'FAILED', 'error' => 'HTTP response error: ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error("SSLCommerz merchantTransID query exception: " . $e->getMessage());
            return ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }

    /**
     * On-demand verification of an order payment via SSLCommerz gateway.
     *
     * @return array{success: bool, message: string, details: ?array}
     */
    public function verifyOrderPayment(Order $order): array
    {
        if ($order->payment_status === 'paid') {
            return [
                'success' => true,
                'message' => "Order #{$order->order_number} is already paid.",
                'details' => null,
            ];
        }

        $queryResult = $this->queryTransactionByOrderNumber($order->order_number);

        // Check for element array in SSLCommerz response
        $element = $queryResult['element'][0] ?? $queryResult;
        $status = strtoupper($element['status'] ?? '');

        if (in_array($status, ['VALID', 'VALIDATED'])) {
            $this->handleSuccess([
                'tran_id' => $order->order_number,
                'val_id' => $element['val_id'] ?? null,
                'amount' => $element['amount'] ?? $order->grand_total,
                'card_type' => $element['card_type'] ?? 'SSLCommerz',
                'bank_tran_id' => $element['bank_tran_id'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => "Payment verified successfully via SSLCommerz! Status: {$status}",
                'details' => $element,
            ];
        }

        // If in sandbox mode, provide an admin-assisted verification option if requested
        if ($this->isSandboxMode()) {
            return [
                'success' => false,
                'message' => "SSLCommerz sandbox query returned status: " . ($status ?: 'NO_RECORD') . ". In Sandbox mode, please complete checkout via the sandbox simulation portal.",
                'details' => $queryResult,
            ];
        }

        return [
            'success' => false,
            'message' => "SSLCommerz verification failed. Status: " . ($status ?: 'NOT_FOUND') . ($element['error'] ?? ''),
            'details' => $queryResult,
        ];
    }
}

