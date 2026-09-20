<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    /**
     * Create order with guest-to-account password handling, address binding, and stock reduction.
     */
    public function order(CheckoutOrderRequest $request): JsonResponse
    {
        $authToken = null;
        $isNewUser = false;
        $user = null;

        $order = DB::transaction(function () use ($request, &$authToken, &$isNewUser, &$user) {
            // 1. Resolve or Create User Account
            $user = $request->user('sanctum') ?? auth('sanctum')->user() ?? $request->user();

            if (! $user) {
                // Check if user with this phone or email already exists
                $user = User::where('phone', $request->customer_phone)
                    ->when($request->filled('customer_email'), function ($q) use ($request) {
                        $q->orWhere('email', $request->customer_email);
                    })
                    ->first();

                // If user doesn't exist and password provided, create account automatically
                if (! $user && $request->filled('password')) {
                    $customerRole = Role::where('slug', 'customer')->first();

                    $user = User::create([
                        'role_id' => $customerRole?->id,
                        'name' => $request->customer_name,
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'password' => Hash::make($request->password),
                        'status' => 'active',
                    ]);

                    $authToken = $user->createToken('checkout-auth')->plainTextToken;
                    $isNewUser = true;
                }
            }

            // 2. Address Binding
            if ($user) {
                $hasDefault = CustomerAddress::where('user_id', $user->id)->where('is_default', true)->exists();

                CustomerAddress::create([
                    'user_id' => $user->id,
                    'type' => 'shipping',
                    'recipient_name' => $request->customer_name,
                    'recipient_phone' => $request->customer_phone,
                    'district' => $request->shipping_district,
                    'city_zone' => $request->shipping_district,
                    'address' => $request->shipping_address,
                    'postal_code' => $request->postal_code,
                    'is_default' => ! $hasDefault,
                ]);
            }

            // 3. Process Items & Lock Stock
            $processedItems = [];
            $subtotal = 0.00;
            $totalProductCost = 0.00;
            $logsToCreate = [];

            foreach ($request->items as $itemData) {
                $variant = ProductVariant::with('product')
                    ->where('id', $itemData['product_variant_id'])
                    ->where('product_id', $itemData['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'items' => ["Invalid product variant selected (ID: {$itemData['product_variant_id']})."],
                    ]);
                }

                $quantity = (int) $itemData['quantity'];

                if ($variant->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient stock for '{$variant->product->title}' ({$variant->size}/{$variant->color}). Only {$variant->stock_quantity} available in inventory.",
                        ],
                    ]);
                }

                // Determine unit prices
                $unitSalePrice = (float) ($variant->sale_price ?? $variant->regular_price ?? $variant->product->discount_price ?? $variant->product->base_selling_price);
                $unitCostPrice = (float) ($variant->cost_price ?? $variant->product->base_cost_price ?? 0);

                $itemSubtotal = round($unitSalePrice * $quantity, 2);
                $itemCost = round($unitCostPrice * $quantity, 2);
                $itemNetProfit = round($itemSubtotal - $itemCost, 2);

                $subtotal += $itemSubtotal;
                $totalProductCost += $itemCost;

                // Decrement stock
                $previousStock = $variant->stock_quantity;
                $variant->decrement('stock_quantity', $quantity);
                $remainingStock = $previousStock - $quantity;

                // Collect for order_items and inventory_logs
                $processedItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->title,
                    'variant_details' => trim("{$variant->size} {$variant->color}"),
                    'quantity' => $quantity,
                    'unit_cost_price' => $unitCostPrice,
                    'unit_sale_price' => $unitSalePrice,
                    'subtotal' => $itemSubtotal,
                    'item_net_profit' => $itemNetProfit,
                    'previous_stock' => $previousStock,
                    'remaining_stock' => $remainingStock,
                ];
            }

            // 4. Coupon Validation & Discount
            $coupon = null;
            $discountAmount = 0.00;

            if ($request->filled('coupon_code')) {
                $couponCode = strtoupper(trim($request->coupon_code));
                $coupon = Coupon::where('code', $couponCode)->where('status', 'active')->first();

                if ($coupon && $coupon->isValid($subtotal)) {
                    if ($coupon->type === 'percent' || $coupon->type === 'percentage') {
                        $discountAmount = ($subtotal * (float) $coupon->discount_value) / 100;
                        if ($coupon->max_discount_amount !== null) {
                            $discountAmount = min($discountAmount, (float) $coupon->max_discount_amount);
                        }
                    } elseif ($coupon->type === 'fixed') {
                        $discountAmount = min((float) $coupon->discount_value, $subtotal);
                    }

                    $discountAmount = round($discountAmount, 2);
                    $coupon->increment('used_count');
                }
            }

            // 5. Delivery Charge Calculation
            $deliveryFee = $request->delivery_method === 'inside-dhaka' ? 60.00 : 120.00;
            // Free delivery threshold at 2000 BDT
            $deliveryCharge = ($subtotal >= 2000.00) ? 0.00 : $deliveryFee;

            // Grand Total
            $grandTotal = max(0.00, round($subtotal + $deliveryCharge - $discountAmount, 2));

            // Estimated Operating Costs & Net Profit
            $packagingCost = 15.00;
            $actualCourierCost = $request->delivery_method === 'inside-dhaka' ? 50.00 : 100.00;
            $paymentGatewayFee = ($request->payment_method === 'sslcommerz')
                ? round($grandTotal * 0.02, 2)
                : 0.00;

            $netProfit = round(($subtotal - $discountAmount) - $totalProductCost - $packagingCost - $actualCourierCost - $paymentGatewayFee, 2);

            // 6. Generate Unique Order Number
            $orderNumber = 'ELV-' . date('ymd') . '-' . strtoupper(Str::random(4));

            // 7. Create Order Record
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id,
                'coupon_id' => $coupon?->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'shipping_district' => $request->shipping_district,
                'shipping_address' => $request->shipping_address,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discountAmount,
                'delivery_charge' => $deliveryCharge,
                'grand_total' => $grandTotal,
                'total_product_cost' => $totalProductCost,
                'packaging_cost' => $packagingCost,
                'actual_courier_cost' => $actualCourierCost,
                'payment_gateway_fee' => $paymentGatewayFee,
                'net_profit' => $netProfit,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'customer_notes' => $request->customer_notes,
                'metadata' => [
                    'postal_code' => $request->postal_code,
                    'delivery_method' => $request->delivery_method,
                ],
            ]);

            // 8. Create Order Items & Inventory Logs
            foreach ($processedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'product_name' => $item['product_name'],
                    'variant_details' => $item['variant_details'],
                    'quantity' => $item['quantity'],
                    'unit_cost_price' => $item['unit_cost_price'],
                    'unit_sale_price' => $item['unit_sale_price'],
                    'subtotal' => $item['subtotal'],
                    'item_net_profit' => $item['item_net_profit'],
                ]);

                InventoryLog::create([
                    'product_variant_id' => $item['product_variant_id'],
                    'transaction_type' => 'sale',
                    'quantity_change' => -$item['quantity'],
                    'remaining_stock' => $item['remaining_stock'],
                    'reference_id' => $order->id,
                    'remarks' => "Sale from Order #{$order->order_number}",
                    'created_by' => $user?->id,
                    'created_at' => now(),
                ]);
            }

            // 9. Record Initial Status History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'updated_by' => $user?->id,
                'comment' => 'Order placed online successfully.',
                'notified_customer' => false,
                'created_at' => now(),
            ]);

            return $order->load(['items', 'coupon']);
        });

        // 10. If payment method is SSLCommerz, initiate payment session
        $redirectUrl = null;
        if (strtolower($request->payment_method) === 'sslcommerz') {
            try {
                $sslcommerz = app(\App\Services\Payment\SSLCommerzService::class);
                $initResult = $sslcommerz->initiatePayment($order);
                if ($initResult['success'] && ! empty($initResult['redirect_url'])) {
                    $redirectUrl = $initResult['redirect_url'];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SSLCommerz auto-initiation warning: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'redirect_url' => $redirectUrl,
            'payment_type' => $redirectUrl ? 'online' : 'cod',
            'data' => new OrderResource($order),
            'auth' => $authToken ? [
                'token' => $authToken,
                'is_new_user' => (bool) $isNewUser,
                'user' => $user ? new UserResource($user) : null,
                'message' => 'Account created successfully. Use this token for authenticated requests.',
            ] : null,
        ], 201);
    }

    /**
     * Get order details by order_number for checkout confirmation and status polling.
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::with(['items', 'coupon'])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}

