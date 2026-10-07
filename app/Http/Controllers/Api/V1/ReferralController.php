<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\ReferralCommission;
use App\Models\ReferralSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Get customer referral dashboard statistics and settings.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        $settings = ReferralSetting::getSettings();

        $totalReferredUsers = User::where('referred_by_id', $user->id)->count();
        $convertedOrdersCount = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $user->referral_code,
                'referral_link' => $user->referral_link,
                'stats' => [
                    'total_referred_users' => $totalReferredUsers,
                    'converted_orders_count' => $convertedOrdersCount,
                    'available_balance' => (float) $wallet->balance,
                    'pending_balance' => (float) $wallet->pending_balance,
                    'lifetime_earned' => (float) $wallet->lifetime_earned,
                ],
                'settings' => [
                    'is_enabled' => (bool) $settings->is_enabled,
                    'commission_type' => $settings->commission_type,
                    'commission_value' => (float) $settings->commission_value,
                    'minimum_order_value' => (float) $settings->minimum_order_value,
                    'minimum_payout_threshold' => (float) $settings->minimum_payout_threshold,
                    'reward_frequency' => $settings->reward_frequency,
                ],
            ],
        ]);
    }

    /**
     * Get customer wallet ledger transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get customer withdrawal / payout requests history.
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();

        $payouts = PayoutRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payouts->items(),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    /**
     * Submit a new withdrawal / payout request.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'in:bkash,nagad,rocket,bank_transfer'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:150'],
            'bank_name' => ['required_if:method,bank_transfer', 'nullable', 'string', 'max:150'],
            'branch_name' => ['nullable', 'string', 'max:150'],
            'routing_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'bank_name.required_if' => 'Please provide bank name for bank transfer.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        try {
            $payout = $this->referralService->requestPayout($user, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully.',
                'data' => $payout,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit withdrawal request: ' . $e->getMessage(),
            ], 500);
        }
    }
}

