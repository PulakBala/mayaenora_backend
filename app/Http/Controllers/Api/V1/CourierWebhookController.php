<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Courier\CourierManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourierWebhookController extends Controller
{
    /**
     * Handle incoming delivery status webhooks from courier partners.
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $provider = strtolower(trim($provider));

        if (! in_array($provider, ['steadfast', 'pathao', 'redx'])) {
            return response()->json([
                'success' => false,
                'message' => "Unsupported courier provider: [{$provider}]",
            ], 404);
        }

        try {
            $processed = CourierManager::handleWebhook($provider, $request);

            return response()->json([
                'success' => true,
                'message' => $processed ? 'Webhook handled and order updated.' : 'Webhook received (no matching order updated).',
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Courier webhook processing error ({$provider}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing courier webhook.',
            ], 500);
        }
    }
}

