<?php

namespace App\Http\Controllers;

use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TokenController extends Controller
{
    public function index()
    {
        return view('tokens.index');
    }

        public function indexwallet()
    {
        return view('wallet.index');
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('tokens.index')
                ->with('error', 'Invalid session.');
        }

        $stripe  = new \Stripe\StripeClient(config('cashier.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return redirect()->route('tokens.index')
                ->with('error', 'Payment not completed.');
        }

        $alreadyProcessed = TokenTransaction::where(
            'stripe_payment_intent_id',
            $session->payment_intent
        )->exists();

        if (!$alreadyProcessed) {
            $this->creditTokens(
                Auth::user(),
                (int) $session->metadata->tokens,
                $session->payment_intent
            );
        }

        return redirect()->route('wallet.index')
            ->with('message', 'Tokens added to your wallet successfully!');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'tokens'    => 'required|integer|min:1',
            'price_usd' => 'required|numeric|min:0.50',
        ]);

        $user         = Auth::user();
        $tokens       = (int) $request->tokens;
        $priceUsd     = (float) $request->price_usd;
        $priceInCents = (int) round($priceUsd * 100);

        $session = $user->checkout([
            [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $priceInCents,
                    'product_data' => [
                        'name'        => "{$tokens} Taongaf Tokens",
                        'description' => "Purchase {$tokens} tokens to spend on content.",
                    ],
                ],
                'quantity' => 1,
            ],
        ], [
            'success_url' => route('tokens.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('tokens.index'),
            'metadata'    => [
                'user_id' => $user->id,
                'tokens'  => $tokens,
                'type'    => 'token_purchase',
            ],
        ]);

        return redirect($session->url);
    }

    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('cashier.webhook.secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if (($session->metadata->type ?? '') === 'token_purchase') {
                $alreadyProcessed = TokenTransaction::where(
                    'stripe_payment_intent_id',
                    $session->payment_intent
                )->exists();

                if (!$alreadyProcessed) {
                    $user = User::findOrFail($session->metadata->user_id);
                    $this->creditTokens(
                        $user,
                        (int) $session->metadata->tokens,
                        $session->payment_intent
                    );
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function creditTokens(User $user, int $tokens, string $paymentIntentId): void
    {
        DB::transaction(function () use ($user, $tokens, $paymentIntentId) {
            $wallet = $user->getOrCreateWallet();
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $balanceBefore = $wallet->token_balance;

            // Only increment token_balance — not total_earned
            // total_earned is reserved for publisher content earnings only
            $wallet->increment('token_balance', $tokens);

            TokenTransaction::create([
                'user_id'                  => $user->id,
                'wallet_id'                => $wallet->id,
                'type'                     => 'purchase',
                'amount'                   => $tokens,
                'direction'                => 'credit',
                'balance_before'           => $balanceBefore,
                'balance_after'            => $balanceBefore + $tokens,
                'description'              => "Purchased {$tokens} tokens",
                'stripe_payment_intent_id' => $paymentIntentId,
            ]);
        });
    }
}