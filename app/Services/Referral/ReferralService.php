<?php

namespace App\Services\Referral;

use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\ReferralCommission;
use App\Models\ReferralSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralService
{
    /**
     * Process referral commission when an order is marked as 'delivered'.
     */
    public function processOrderDelivered(Order $order): ?ReferralCommission
    {
        // 1. Program check
        $settings = ReferralSetting::getSettings();
        if (! $settings->is_enabled) {
            return null;
        }

        // 2. Prevent duplicate commission
        if ($order->is_referral_commission_awarded) {
            return null;
        }

        // 3. Resolve Referrer
        $customer = $order->user;
        $referrerId = $order->referrer_id ?? $customer?->referred_by_id;

        if (! $referrerId) {
            return null;
        }

        $referrer = User::find($referrerId);
        if (! $referrer || ($customer && $referrer->id === $customer->id)) {
            return null; // Self-referral prohibited
        }

        // 4. Minimum Order Value Check (using subtotal or grand total without delivery charge)
        $qualifyingAmount = (float) ($order->subtotal_amount > 0 ? $order->subtotal_amount : $order->grand_total);
        if ($qualifyingAmount < (float) $settings->minimum_order_value) {
            return null;
        }

        // 5. One-time vs Recurring check
        if ($settings->reward_frequency === 'first_order_only' && $customer) {
            $previousDeliveredCount = Order::where('user_id', $customer->id)
                ->where('id', '!=', $order->id)
                ->where('order_status', 'delivered')
                ->count();

            if ($previousDeliveredCount > 0) {
                return null; // Only first delivered order qualifies
            }
        }

        // 6. Calculate Commission
        $commissionAmount = 0.00;
        if ($settings->commission_type === 'percentage') {
            $commissionAmount = round(($qualifyingAmount * (float) $settings->commission_value) / 100, 2);
        } else {
            $commissionAmount = round((float) $settings->commission_value, 2);
        }

        if ($commissionAmount <= 0) {
            return null;
        }

        // 7. Atomic DB Transaction
        return DB::transaction(function () use ($referrer, $customer, $order, $settings, $qualifyingAmount, $commissionAmount) {
            // A. Commission Audit Entry
            $commissionRecord = ReferralCommission::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $customer?->id ?? $order->user_id,
                'order_id' => $order->id,
                'order_amount' => $qualifyingAmount,
                'commission_type' => $settings->commission_type,
                'commission_rate' => $settings->commission_value,
                'commission_amount' => $commissionAmount,
                'status' => 'approved',
                'notes' => "Commission earned from Order #{$order->order_number}",
            ]);

            // B. Credit Referrer Wallet
            $wallet = $referrer->getOrCreateWallet();
            $balanceAfter = round((float) $wallet->balance + $commissionAmount, 2);

            $wallet->balance = $balanceAfter;
            $wallet->lifetime_earned = round((float) $wallet->lifetime_earned + $commissionAmount, 2);
            $wallet->save();

            // C. Transaction Ledger
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $referrer->id,
                'type' => 'credit',
                'source' => 'referral_commission',
                'amount' => $commissionAmount,
                'balance_after' => $balanceAfter,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Referral commission from Order #{$order->order_number} ({$settings->commission_type}: {$settings->commission_value})",
                'created_at' => now(),
            ]);

            // D. Stamp Order
            $order->referrer_id = $referrer->id;
            $order->referral_commission_amount = $commissionAmount;
            $order->is_referral_commission_awarded = true;
            $order->saveQuietly();

            return $commissionRecord;
        });
    }

    /**
     * Submit customer withdrawal / payout request.
     */
    public function requestPayout(User $user, array $data): PayoutRequest
    {
        $settings = ReferralSetting::getSettings();
        if (! $settings->is_enabled) {
            throw ValidationException::withMessages([
                'amount' => ['Referral payout system is temporarily disabled.'],
            ]);
        }

        $wallet = $user->getOrCreateWallet();
        $amount = round((float) $data['amount'], 2);
        $minThreshold = (float) $settings->minimum_payout_threshold;

        if ($amount < $minThreshold) {
            throw ValidationException::withMessages([
                'amount' => ["Minimum withdrawal amount is ৳" . number_format($minThreshold, 2) . "."],
            ]);
        }

        if ($amount > (float) $wallet->balance) {
            throw ValidationException::withMessages([
                'amount' => ["Insufficient available balance. You have ৳" . number_format($wallet->balance, 2) . " available."],
            ]);
        }

        // Check for existing pending request
        $hasPending = PayoutRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'amount' => ['You already have an active pending payout request. Please wait until it is processed.'],
            ]);
        }

        return DB::transaction(function () use ($user, $wallet, $amount, $data) {
            $payoutNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // Deduct available balance, tie up in pending balance
            $balanceAfter = round((float) $wallet->balance - $amount, 2);
            $wallet->balance = $balanceAfter;
            $wallet->pending_balance = round((float) $wallet->pending_balance + $amount, 2);
            $wallet->save();

            $payout = PayoutRequest::create([
                'payout_number' => $payoutNumber,
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $data['method'],
                'account_number' => trim($data['account_number']),
                'account_name' => ! empty($data['account_name']) ? trim($data['account_name']) : null,
                'bank_name' => ! empty($data['bank_name']) ? trim($data['bank_name']) : null,
                'branch_name' => ! empty($data['branch_name']) ? trim($data['branch_name']) : null,
                'routing_number' => ! empty($data['routing_number']) ? trim($data['routing_number']) : null,
                'status' => 'pending',
                'admin_notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
            ]);

            // Log ledger transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'source' => 'payout_withdrawal',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_type' => PayoutRequest::class,
                'reference_id' => $payout->id,
                'description' => "Payout request ({$payoutNumber}) via " . strtoupper($payout->method),
                'created_at' => now(),
            ]);

            return $payout;
        });
    }

    /**
     * Admin approves and marks payout request as Paid.
     */
    public function approvePayout(PayoutRequest $payout, User $admin, string $transactionRef, ?string $notes = null): PayoutRequest
    {
        if ($payout->status !== 'pending') {
            throw new \RuntimeException("Payout request is already {$payout->status}.");
        }

        return DB::transaction(function () use ($payout, $admin, $transactionRef, $notes) {
            $wallet = $payout->user->getOrCreateWallet();

            // Release pending balance
            $wallet->pending_balance = max(0.00, round((float) $wallet->pending_balance - (float) $payout->amount, 2));
            $wallet->save();

            $payout->update([
                'status' => 'paid',
                'transaction_reference' => trim($transactionRef),
                'admin_notes' => $notes ? trim($notes) : $payout->admin_notes,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $payout;
        });
    }

    /**
     * Admin rejects payout request and refunds balance back to customer wallet.
     */
    public function rejectPayout(PayoutRequest $payout, User $admin, string $reason): PayoutRequest
    {
        if ($payout->status !== 'pending') {
            throw new \RuntimeException("Payout request is already {$payout->status}.");
        }

        return DB::transaction(function () use ($payout, $admin, $reason) {
            $wallet = $payout->user->getOrCreateWallet();
            $amount = (float) $payout->amount;

            // Release pending balance and refund back to available balance
            $wallet->pending_balance = max(0.00, round((float) $wallet->pending_balance - $amount, 2));
            $balanceAfter = round((float) $wallet->balance + $amount, 2);
            $wallet->balance = $balanceAfter;
            $wallet->save();

            $payout->update([
                'status' => 'rejected',
                'rejection_reason' => trim($reason),
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            // Create refund credit transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $payout->user_id,
                'type' => 'credit',
                'source' => 'payout_refund',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_type' => PayoutRequest::class,
                'reference_id' => $payout->id,
                'description' => "Refund for rejected payout ({$payout->payout_number}): " . trim($reason),
                'created_at' => now(),
            ]);

            return $payout;
        });
    }
}

