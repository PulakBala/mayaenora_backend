<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ValidateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Validate coupon code against current subtotal.
     */
    public function validateCoupon(ValidateCouponRequest $request): JsonResponse
    {
        $code = strtoupper(trim($request->input('code')));
        $subtotal = (float) $request->input('subtotal');

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid promo code. Please check and try again.',
            ], 422);
        }

        if ($coupon->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is no longer active.',
            ], 422);
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is not yet valid.',
            ], 422);
        }

        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has expired.',
            ], 422);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon usage limit has been reached.',
            ], 422);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum purchase of ৳{$coupon->min_order_amount} required for this coupon.",
            ], 422);
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->type === 'percent' || $coupon->type === 'percentage') {
            $discountAmount = ($subtotal * (float) $coupon->discount_value) / 100;
            if ($coupon->max_discount_amount !== null) {
                $discountAmount = min($discountAmount, (float) $coupon->max_discount_amount);
            }
        } elseif ($coupon->type === 'fixed') {
            $discountAmount = min((float) $coupon->discount_value, $subtotal);
        }

        $discountAmount = round($discountAmount, 2);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' applied successfully!",
            'data' => [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->discount_value,
                'discount_amount' => $discountAmount,
            ],
        ]);
    }
}

