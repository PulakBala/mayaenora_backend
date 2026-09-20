<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\SSLCommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SSLCommerzController extends Controller
{
    public function __construct(
        protected SSLCommerzService $sslcommerz
    ) {}

    /**
     * Initiate payment for an existing order.
     */
    public function initiate(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This order is already marked as paid.',
            ], 400);
        }

        $result = $this->sslcommerz->initiatePayment($order);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Handle SSLCommerz Success Callback.
     */
    public function success(Request $request): RedirectResponse
    {
        $payload = $request->all();
        Log::info('SSLCommerz success callback received:', $payload);

        try {
            $order = $this->sslcommerz->handleSuccess($payload);

            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');
            $tranId = $payload['bank_tran_id'] ?? $payload['tran_id'] ?? '';

            return redirect()->away("{$frontendUrl}/order-success?order_number={$order->order_number}&status=paid&tran_id={$tranId}");
        } catch (\Throwable $e) {
            Log::error('SSLCommerz success callback error: ' . $e->getMessage());
            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

            return redirect()->away("{$frontendUrl}/order-failed?error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Handle SSLCommerz Fail Callback.
     */
    public function fail(Request $request): RedirectResponse
    {
        $payload = $request->all();
        Log::warning('SSLCommerz fail callback received:', $payload);

        $orderNumber = $payload['tran_id'] ?? '';

        try {
            if ($orderNumber) {
                $this->sslcommerz->handleFail($payload);
            }
        } catch (\Throwable $e) {
            Log::error('SSLCommerz fail handler error: ' . $e->getMessage());
        }

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

        return redirect()->away("{$frontendUrl}/order-failed?order_number={$orderNumber}&status=failed");
    }

    /**
     * Handle SSLCommerz Cancel Callback.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $payload = $request->all();
        Log::info('SSLCommerz cancel callback received:', $payload);

        $orderNumber = $payload['tran_id'] ?? '';

        try {
            if ($orderNumber) {
                $this->sslcommerz->handleCancel($payload);
            }
        } catch (\Throwable $e) {
            Log::error('SSLCommerz cancel handler error: ' . $e->getMessage());
        }

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/');

        return redirect()->away("{$frontendUrl}/checkout?status=cancelled&order_number={$orderNumber}");
    }

    /**
     * Handle SSLCommerz Instant Payment Notification (IPN).
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('SSLCommerz IPN notification received:', $payload);

        $handled = $this->sslcommerz->handleIPN($payload);

        return response()->json([
            'success' => $handled,
            'message' => $handled ? 'IPN handled successfully.' : 'IPN received but unhandled.',
        ]);
    }

    /**
     * Local Sandbox Simulation Checkout Page.
     * Renders when running locally in sandbox mode so test checkouts can be fully simulated offline.
     */
    public function sandboxCheckout(Request $request): Response
    {
        $orderNumber = $request->query('order');
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        $successUrl = url('/api/v1/payment/sslcommerz/success');
        $failUrl = url('/api/v1/payment/sslcommerz/fail');
        $cancelUrl = url('/api/v1/payment/sslcommerz/cancel');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSLCommerz Sandbox Test Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4 font-sans antialiased text-slate-800">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-5 text-white text-center">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/30 text-xs font-semibold uppercase tracking-wider mb-2">
                Sandbox Test Simulator
            </div>
            <h1 class="text-xl font-extrabold tracking-tight">SSLCommerz Hosted Checkout</h1>
            <p class="text-xs text-blue-100 mt-1">Cards • bKash • Nagad • Rocket • Internet Banking</p>
        </div>

        <div class="p-6 space-y-4">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Invoice / Order #</span>
                    <span class="font-bold text-slate-900">{$order->order_number}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Customer</span>
                    <span class="font-medium text-slate-800">{$order->customer_name} ({$order->customer_phone})</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2">
                    <span class="font-bold text-slate-700">Amount Payable</span>
                    <span class="text-base font-extrabold text-blue-700">৳{$order->grand_total} BDT</span>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 text-center">
                Select an outcome below to test your store's end-to-end payment lifecycle:
            </p>

            <form action="{$successUrl}" method="POST" class="space-y-2">
                <input type="hidden" name="tran_id" value="{$order->order_number}">
                <input type="hidden" name="val_id" value="mock_val_{$order->id}_test">
                <input type="hidden" name="amount" value="{$order->grand_total}">
                <input type="hidden" name="card_type" value="bKash-Online">
                <input type="hidden" name="bank_tran_id" value="MOCK-TXN-{$order->id}">
                <input type="hidden" name="status" value="VALID">
                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer flex items-center justify-center gap-2">
                    <span>✓ Simulate Successful Payment (bKash/Card)</span>
                </button>
            </form>

            <form action="{$failUrl}" method="POST">
                <input type="hidden" name="tran_id" value="{$order->order_number}">
                <input type="hidden" name="error" value="Insufficient balance or card declined">
                <button type="submit" class="w-full py-2 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs rounded-xl border border-rose-200 transition-colors cursor-pointer">
                    ✕ Simulate Failed Payment
                </button>
            </form>

            <form action="{$cancelUrl}" method="POST">
                <input type="hidden" name="tran_id" value="{$order->order_number}">
                <button type="submit" class="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-xs rounded-xl transition-colors cursor-pointer">
                    Cancel & Return to Store
                </button>
            </form>
        </div>
    </div>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}

