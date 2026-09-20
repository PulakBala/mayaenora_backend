<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * Fetch authenticated customer's profile, default address, and address list.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'customerAddresses']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 200);
    }

    /**
     * Update customer profile info and optionally change password.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150', "unique:users,email,{$user->id}"],
            'phone' => ['nullable', 'string', 'max:20', "unique:users,phone,{$user->id}"],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided current password does not match.',
                    'errors' => ['current_password' => ['Current password is incorrect.']],
                ], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name = trim($request->name);
        if ($request->has('email')) {
            $user->email = $request->filled('email') ? strtolower(trim($request->email)) : null;
        }
        if ($request->has('phone')) {
            $user->phone = $request->filled('phone') ? trim($request->phone) : null;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->load(['role', 'customerAddresses'])),
        ], 200);
    }

    /**
     * List customer's orders strictly scoped to user_id.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with(['items'])
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 200);
    }

    /**
     * View single order details. Ensures strict ownership (returns 403 if unauthorized).
     */
    public function showOrder(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'coupon', 'statusHistories'])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Strict authorization check: Only the order owner or staff can view
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to view this order.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ], 200);
    }

    /**
     * Add a new customer shipping address.
     */
    public function storeAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'district' => ['required', 'string', 'max:60'],
            'city_zone' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'in:shipping,billing,both'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $isDefault = (bool) $request->input('is_default', false);

        // If this is default, or user's first address, set as default
        if ($isDefault || $user->customerAddresses()->count() === 0) {
            $user->customerAddresses()->update(['is_default' => false]);
            $isDefault = true;
        }

        $address = $user->customerAddresses()->create([
            'type' => $request->input('type', 'shipping'),
            'recipient_name' => trim($request->recipient_name),
            'recipient_phone' => trim($request->recipient_phone),
            'district' => trim($request->district),
            'city_zone' => $request->input('city_zone') ? trim($request->city_zone) : trim($request->district),
            'address' => trim($request->address),
            'postal_code' => $request->input('postal_code') ? trim($request->postal_code) : null,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => new CustomerAddressResource($address),
        ], 201);
    }

    /**
     * Update an existing address strictly scoped to user.
     */
    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->customerAddresses()->find($id);

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'district' => ['required', 'string', 'max:60'],
            'city_zone' => ['nullable', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'in:shipping,billing,both'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_default')) {
            $user->customerAddresses()->where('id', '!=', $id)->update(['is_default' => false]);
            $address->is_default = true;
        } elseif ($request->has('is_default')) {
            $address->is_default = false;
        }

        $address->update([
            'type' => $request->input('type', $address->type),
            'recipient_name' => trim($request->recipient_name),
            'recipient_phone' => trim($request->recipient_phone),
            'district' => trim($request->district),
            'city_zone' => $request->input('city_zone') ? trim($request->city_zone) : trim($request->district),
            'address' => trim($request->address),
            'postal_code' => $request->input('postal_code') ? trim($request->postal_code) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => new CustomerAddressResource($address),
        ], 200);
    }

    /**
     * Delete an address strictly scoped to user.
     */
    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->customerAddresses()->find($id);

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If default was deleted, assign another address as default if exists
        if ($wasDefault) {
            $user->customerAddresses()->first()?->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ], 200);
    }

    /**
     * Set specific address as default.
     */
    public function setDefaultAddress(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->customerAddresses()->find($id);

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found or unauthorized.',
            ], 404);
        }

        $user->customerAddresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default shipping address updated.',
            'data' => new CustomerAddressResource($address),
        ], 200);
    }
}

