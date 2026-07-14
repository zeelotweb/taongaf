<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\PlatformCredit;
use App\Models\ReferralCommission;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommerceService
{
    /**
     * Calculate commission split for a profile commerce sale
     */
    public function calculateSplit(int $saleAmount, bool $publisherIsHustler): array
    {
        $hustlerBase      = config('commerce.hustler_base');
        $hustlerCommission = config('commerce.hustler_commission');
        $profileOwnerShare = config('commerce.profile_owner_share');
        $platformCut       = config('commerce.platform_commerce_cut');

        $publisherEarned     = (int) floor($saleAmount * ($hustlerBase / 100));
        $hustlerEarned       = (int) floor($saleAmount * ($hustlerCommission / 100));
        $profileOwnerEarned  = (int) floor($saleAmount * ($profileOwnerShare / 100));
        $platformEarned      = $saleAmount - $publisherEarned - $hustlerEarned - $profileOwnerEarned;

        // Publisher IS the hustler — they get both shares
        if ($publisherIsHustler) {
            return [
                'publisher_earned'     => $publisherEarned + $hustlerEarned,
                'hustler_earned'       => $hustlerEarned,
                'profile_owner_earned' => $profileOwnerEarned,
                'platform_earned'      => $platformEarned,
                'publisher_is_hustler' => true,
            ];
        }

        return [
            'publisher_earned'     => $publisherEarned,
            'hustler_earned'       => $hustlerEarned,
            'profile_owner_earned' => $profileOwnerEarned,
            'platform_earned'      => $platformEarned,
            'publisher_is_hustler' => false,
        ];
    }

    /**
     * Process a sale made through a promotion
     */
    public function processSale(
        Promotion $promotion,
        Purchase  $purchase,
        User      $buyer,
        User      $publisher,
        string    $attributedVia
    ): ReferralCommission {
        $publisherIsHustler = $publisher->id === $promotion->hustler_id;
        $split = $this->calculateSplit($purchase->tokens_spent, $publisherIsHustler);

        return DB::transaction(function () use (
            $promotion,
            $purchase,
            $buyer,
            $publisher,
            $attributedVia,
            $split,
            $publisherIsHustler
        ) {
            // Lock all wallets
            $publisherWallet   = Wallet::lockForUpdate()->where('user_id', $publisher->id)->first();
            $hustlerWallet     = Wallet::lockForUpdate()->where('user_id', $promotion->hustler_id)->first();
            $profileOwnerWallet = Wallet::lockForUpdate()->where('user_id', $promotion->profile_owner_id)->first();

            // Adjust publisher earnings
            // Standard PurchaseService already credited 90% — we need to adjust
            $standardPublisherEarned = (int) floor($purchase->tokens_spent * (config('platform.publisher_share') / 100));
            $adjustedPublisherEarned = $split['publisher_earned'];
            $difference              = $standardPublisherEarned - $adjustedPublisherEarned;

            if ($difference > 0 && !$publisherIsHustler) {
                // Deduct the difference from publisher (they gave up 5% to profile commerce)
                $publisherWallet?->decrement('token_balance', $difference);
                $publisherWallet?->decrement('earnings_balance', $difference);
            }

            // Credit hustler
            if (!$publisherIsHustler && $hustlerWallet) {
                $hustlerWallet->increment('token_balance', $split['hustler_earned']);
                $hustlerWallet->increment('earnings_balance', $split['hustler_earned']);
                $hustlerWallet->increment('total_earned', $split['hustler_earned']);

                TokenTransaction::create([
                    'user_id'              => $promotion->hustler_id,
                    'wallet_id'            => $hustlerWallet->id,
                    'type'                 => 'earn',
                    'amount'               => $split['hustler_earned'],
                    'direction'            => 'credit',
                    'balance_before'       => $hustlerWallet->token_balance - $split['hustler_earned'],
                    'balance_after'        => $hustlerWallet->token_balance,
                    'transactionable_type' => Purchase::class,
                    'transactionable_id'   => $purchase->id,
                    'description'          => 'Hustler commission from: ' . $purchase->purchasable?->title,
                ]);
            } elseif ($publisherIsHustler && $publisherWallet) {
                // Publisher gets hustler commission on top
                $publisherWallet->increment('token_balance', $split['hustler_earned']);
                $publisherWallet->increment('earnings_balance', $split['hustler_earned']);
                $publisherWallet->increment('total_earned', $split['hustler_earned']);

                TokenTransaction::create([
                    'user_id'              => $publisher->id,
                    'wallet_id'            => $publisherWallet->id,
                    'type'                 => 'earn',
                    'amount'               => $split['hustler_earned'],
                    'direction'            => 'credit',
                    'balance_before'       => $publisherWallet->token_balance - $split['hustler_earned'],
                    'balance_after'        => $publisherWallet->token_balance,
                    'transactionable_type' => Purchase::class,
                    'transactionable_id'   => $purchase->id,
                    'description'          => 'Self-hustle commission bonus',
                ]);
            }

            // Credit profile owner
            if ($profileOwnerWallet) {
                $profileOwnerWallet->increment('token_balance', $split['profile_owner_earned']);
                $profileOwnerWallet->increment('earnings_balance', $split['profile_owner_earned']);
                $profileOwnerWallet->increment('total_earned', $split['profile_owner_earned']);

                TokenTransaction::create([
                    'user_id'              => $promotion->profile_owner_id,
                    'wallet_id'            => $profileOwnerWallet->id,
                    'type'                 => 'earn',
                    'amount'               => $split['profile_owner_earned'],
                    'direction'            => 'credit',
                    'balance_before'       => $profileOwnerWallet->token_balance - $split['profile_owner_earned'],
                    'balance_after'        => $profileOwnerWallet->token_balance,
                    'transactionable_type' => Promotion::class,
                    'transactionable_id'   => $promotion->id,
                    'description'          => 'Profile commerce equity earnings',
                ]);
            }

            // Update promotion totals
            $promotion->increment('total_sales_tokens', $purchase->tokens_spent);
            $promotion->increment('hustler_commission_earned', $split['hustler_earned']);
            $promotion->increment('profile_owner_earned', $split['profile_owner_earned']);
            $promotion->increment('platform_earned', $split['platform_earned']);

            // Create commission record
            return ReferralCommission::create([
                'promotion_id'         => $promotion->id,
                'purchase_id'          => $purchase->id,
                'buyer_id'             => $buyer->id,
                'hustler_id'           => $promotion->hustler_id,
                'profile_owner_id'     => $promotion->profile_owner_id,
                'publisher_id'         => $publisher->id,
                'sale_amount'          => $purchase->tokens_spent,
                'publisher_earned'     => $split['publisher_earned'],
                'hustler_earned'       => $split['hustler_earned'],
                'profile_owner_earned' => $split['profile_owner_earned'],
                'platform_earned'      => $split['platform_earned'],
                'publisher_is_hustler' => $publisherIsHustler,
                'attributed_via'       => $attributedVia,
            ]);
        });
    }

    /**
     * Create a promotion request
     */
    public function createPromotion(
        User   $hustler,
        User   $profileOwner,
        object $promotable,
        string $serviceType
    ): array {
        // Check hustler eligibility
        if (!$hustler->isEligibleToHustle()) {
            return ['success' => false, 'message' => 'You are not yet eligible to hustle.'];
        }

        // Check profile owner has commerce enabled
        $settings = $profileOwner->profileCommerceSetting;
        if (!$settings || !$settings->is_enabled) {
            return ['success' => false, 'message' => 'This profile does not have commerce enabled.'];
        }

        // Check service type is allowed
        if (!$settings->allowsService($serviceType)) {
            return ['success' => false, 'message' => 'This service type is not allowed on this profile.'];
        }

        // Check hustler has enough tokens for flat promotion fee
        if ($serviceType === 'promotion' && $settings->promotion_fee > 0) {
            $wallet = $hustler->getOrCreateWallet();
            if (!$wallet->hasEnoughTokens($settings->promotion_fee)) {
                return [
                    'success'  => false,
                    'message'  => 'Insufficient tokens for promotion fee.',
                    'redirect' => route('tokens.index'),
                ];
            }

            // Deduct promotion fee
            DB::transaction(function () use ($hustler, $profileOwner, $settings, $wallet) {
                $wallet = Wallet::lockForUpdate()->find($wallet->id);
                $profileOwnerWallet = $profileOwner->getOrCreateWallet();
                $profileOwnerWallet = Wallet::lockForUpdate()->find($profileOwnerWallet->id);

                $balanceBefore = $wallet->token_balance;
                $wallet->decrement('token_balance', $settings->promotion_fee);
                $wallet->increment('total_spent', $settings->promotion_fee);

                // Profile owner gets flat fee immediately
                $profileOwnerWallet->increment('token_balance', $settings->promotion_fee);
                $profileOwnerWallet->increment('earnings_balance', $settings->promotion_fee);
                $profileOwnerWallet->increment('total_earned', $settings->promotion_fee);

                TokenTransaction::create([
                    'user_id'        => $hustler->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'spend',
                    'amount'         => $settings->promotion_fee,
                    'direction'      => 'debit',
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceBefore - $settings->promotion_fee,
                    'description'    => 'Promotion fee paid to ' . $profileOwner->name,
                ]);
            });
        }

        $status = $settings->auto_approve ? 'active' : 'pending';

        $promotion = Promotion::create([
            'hustler_id'       => $hustler->id,
            'profile_owner_id' => $profileOwner->id,
            'promotable_type'  => get_class($promotable),
            'promotable_id'    => $promotable->id,
            'service_type'     => $serviceType,
            'tokens_paid'      => $settings->promotion_fee ?? 0,
            'referral_token'   => Str::random(16),
            'status'