<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly CommerceService $commerce
    ) {}

    public function purchase(
        User    $buyer,
        Model   $content,
        User    $publisher,
        ?string $referralToken   = null,
        ?int    $profileOwnerId  = null
    ): array {
        $tokenPrice = $content->token_price;

        if (!$tokenPrice || $tokenPrice <= 0) {
            return ['success' => false, 'message' => 'This content is free.'];
        }

        if ($buyer->hasPurchased($content)) {
            return ['success' => false, 'message' => 'You already have access to this content.'];
        }

        if ($buyer->id === $publisher->id) {
            return ['success' => false, 'message' => 'You own this content.'];
        }

        $buyerWallet = $buyer->getOrCreateWallet();

        if (!$buyerWallet->hasEnoughTokens($tokenPrice)) {
            return [
                'success'  => false,
                'message'  => 'Insufficient tokens.',
                'redirect' => route('tokens.index'),
            ];
        }

        // Check for active promotion
        $promotion = $this->commerce->resolvePromotion($referralToken, $profileOwnerId);

        // Route through commerce service if promotion exists
        if ($promotion && $promotion->profile_owner_id !== $publisher->id) {
            return $this->purchaseViaCommerce(
                $buyer,
                $content,
                $publisher,
                $buyerWallet,
                $tokenPrice,
                $promotion,
                $referralToken ? 'referral_link' : 'session_window'
            );
        }

        // Standard purchase
        return $this->standardPurchase(
            $buyer,
            $content,
            $publisher,
            $buyerWallet,
            $tokenPrice
        );
    }

    private function standardPurchase(
        User  $buyer,
        Model $content,
        User  $publisher,
        $buyerWallet,
        int   $tokenPrice
    ): array {
        $platformCutPercent = config('platform.content_cut') / 100;
        $platformCut        = (int) floor($tokenPrice * $platformCutPercent);
        $publisherEarns     = $tokenPrice - $platformCut;

        DB::transaction(function () use (
            $buyer,
            $buyerWallet,
            $content,
            $publisher,
            $tokenPrice,
            $platformCut,
            $publisherEarns
        ) {
            $buyerWallet      = \App\Models\Wallet::lockForUpdate()->find($buyerWallet->id);
            $publisherWallet  = $publisher->getOrCreateWallet();
            $publisherWallet  = \App\Models\Wallet::lockForUpdate()->find($publisherWallet->id);

            $buyerBalanceBefore     = $buyerWallet->token_balance;
            $publisherBalanceBefore = $publisherWallet->token_balance;

            $buyerWallet->decrement('token_balance', $tokenPrice);
            $buyerWallet->increment('total_spent', $tokenPrice);

            $publisherWallet->increment('token_balance', $publisherEarns);
            $publisherWallet->increment('earnings_balance', $publisherEarns);
            $publisherWallet->increment('total_earned', $publisherEarns);

            $purchase = Purchase::create([
                'user_id'          => $buyer->id,
                'purchasable_type' => get_class($content),
                'purchasable_id'   => $content->id,
                'tokens_spent'     => $tokenPrice,
                'publisher_id'     => $publisher->id,
                'publisher_earned' => $publisherEarns,
                'platform_cut'     => $platformCut,
                'is_active'        => true,
            ]);

            TokenTransaction::create([
                'user_id'              => $buyer->id,
                'wallet_id'            => $buyerWallet->id,
                'type'                 => 'spend',
                'amount'               => $tokenPrice,
                'direction'            => 'debit',
                'balance_before'       => $buyerBalanceBefore,
                'balance_after'        => $buyerBalanceBefore - $tokenPrice,
                'transactionable_type' => Purchase::class,
                'transactionable_id'   => $purchase->id,
                'description'          => 'Purchased: ' . $content->title,
            ]);

            TokenTransaction::create([
                'user_id'              => $publisher->id,
                'wallet_id'            => $publisherWallet->id,
                'type'                 => 'earn',
                'amount'               => $publisherEarns,
                'direction'            => 'credit',
                'balance_before'       => $publisherBalanceBefore,
                'balance_after'        => $publisherBalanceBefore + $publisherEarns,
                'transactionable_type' => Purchase::class,
                'transactionable_id'   => $purchase->id,
                'description'          => 'Earned from: ' . $content->title,
            ]);
        });

        return ['success' => true, 'message' => 'Purchase successful! You now have access.'];
    }

    private function purchaseViaCommerce(
        User      $buyer,
        Model     $content,
        User      $publisher,
        $buyerWallet,
        int       $tokenPrice,
        $promotion,
        string    $attributedVia
    ): array {
        // Commerce split — publisher gets 85% base
        $publisherEarns = (int) floor($tokenPrice * (config('commerce.hustler_base') / 100));
        $platformCut    = $tokenPrice - $publisherEarns
            - (int) floor($tokenPrice * (config('commerce.hustler_commission') / 100))
            - (int) floor($tokenPrice * (config('commerce.profile_owner_share') / 100));

        $purchase = null;

        DB::transaction(function () use (
            $buyer,
            $buyerWallet,
            $content,
            $publisher,
            $tokenPrice,
            $publisherEarns,
            $platformCut,
            &$purchase
        ) {
            $buyerWallet     = \App\Models\Wallet::lockForUpdate()->find($buyerWallet->id);
            $publisherWallet = $publisher->getOrCreateWallet();
            $publisherWallet = \App\Models\Wallet::lockForUpdate()->find($publisherWallet->id);

            $buyerBalanceBefore     = $buyerWallet->token_balance;
            $publisherBalanceBefore = $publisherWallet->token_balance;

            $buyerWallet->decrement('token_balance', $tokenPrice);
            $buyerWallet->increment('total_spent', $tokenPrice);

            $publisherWallet->increment('token_balance', $publisherEarns);
            $publisherWallet->increment('earnings_balance', $publisherEarns);
            $publisherWallet->increment('total_earned', $publisherEarns);

            $purchase = Purchase::create([
                'user_id'          => $buyer->id,
                'purchasable_type' => get_class($content),
                'purchasable_id'   => $content->id,
                'tokens_spent'     => $tokenPrice,
                'publisher_id'     => $publisher->id,
                'publisher_earned' => $publisherEarns,
                'platform_cut'     => $platformCut,
                'is_active'        => true,
            ]);

            TokenTransaction::create([
                'user_id'              => $buyer->id,
                'wallet_id'            => $buyerWallet->id,
                'type'                 => 'spend',
                'amount'               => $tokenPrice,
                'direction'            => 'debit',
                'balance_before'       => $buyerBalanceBefore,
                'balance_after'        => $buyerBalanceBefore - $tokenPrice,
                'transactionable_type' => Purchase::class,
                'transactionable_id'   => $purchase->id,
                'description'          => 'Purchased: ' . $content->title,
            ]);

            TokenTransaction::create([
                'user_id'              => $publisher->id,
                'wallet_id'            => $publisherWallet->id,
                'type'                 => 'earn',
                'amount'               => $publisherEarns,
                'direction'            => 'credit',
                'balance_before'       => $publisherBalanceBefore,
                'balance_after'        => $publisherBalanceBefore + $publisherEarns,
                'transactionable_type' => Purchase::class,
                'transactionable_id'   => $purchase->id,
                'description'          => 'Earned from: ' . $content->title,
            ]);
        });

        // Process commerce commissions
        $this->commerce->processSale(
            promotion:     $promotion,
            purchase:      $purchase,
            buyer:         $buyer,
            publisher:     $publisher,
            attributedVia: $attributedVia
        );

        return ['success' => true, 'message' => 'Purchase successful! You now have access.'];
    }
}