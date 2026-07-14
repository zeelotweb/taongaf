<?php

namespace App\Http\Controllers;

use App\Models\StudioMembership;
use App\Models\StudioSubscription;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;

class StudioController extends Controller
{
    public function index()
    {
        return view('studio.index');
    }

    public function staff()
    {
        return view('studio.staff');
    }

    public function community()
    {
        return view('studio.community');
    }
    public function commerce()
    {
        return view('studio.commerce');
    }
    public function analytics()
    {
        return view('studio.analytics');
    }

    public function surveys()
    {
        return view('studio.surveys');
    }

    public function surveyCreate()
    {
        return view('studio.survey-form');
    }

    public function surveyEdit(Survey $survey)
    {
        $this->authorize('update', $survey);
        return view('studio.survey-form', compact('survey'));
    }

    public function subscription()
    {
        return view('studio.subscription');
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if ($sessionId) {
            $stripe  = new StripeClient(config('cashier.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                StudioSubscription::updateOrCreate(
                    ['user_id' => Auth::id()],
                    [
                        'stripe_subscription_id' => $session->subscription,
                        'plan'                   => $session->metadata->plan,
                        'price_usd'              => $session->metadata->plan === 'pro' ? 19.99 : 9.99,
                        'status'                 => 'active',
                        'current_period_ends_at' => now()->addMonth(),
                    ]
                );
            }
        }

        return redirect()->route('studio.index');
    }

    public function acceptInvite(string $token)
    {
        $membership = StudioMembership::where('invite_token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $membership->update([
            'status'       => 'active',
            'joined_at'    => now(),
            'invite_token' => null,
        ]);

        return redirect()->route('studio.index')
            ->with('message', 'Welcome to the studio!');
    }
}