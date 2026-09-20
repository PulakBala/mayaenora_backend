<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderHistoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    /**
     * Fetch authenticated customer's order history strictly scoped to user_id.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1 || $perPage > 50) {
            $perPage = 10;
        }

        $orders = $request->user()
            ->orders()
            ->with([
                'items.product.images',
                'items.product.primaryImage',
                'items.variant',
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderHistoryResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 200);
    }
}

