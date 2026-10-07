<?php

namespace Database\Seeders;

use App\Models\ReferralSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class ReferralSettingSeeder extends Seeder
{
    /**
     * Seed referral settings and initialize referral codes & wallets for existing users.
     */
    public function run(): void
    {
        // 1. Default referral settings
        ReferralSetting::getSettings();

        // 2. Populate referral codes & wallets for existing users
        $users = User::all();
        foreach ($users as $user) {
            $updated = false;

            if (empty($user->referral_code)) {
                $user->referral_code = User::generateUniqueReferralCode();
                $updated = true;
            }

            if ($updated) {
                $user->saveQuietly();
            }

            // Ensure wallet exists
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 0.00,
                    'pending_balance' => 0.00,
                    'lifetime_earned' => 0.00,
                ]
            );
        }
    }
}

