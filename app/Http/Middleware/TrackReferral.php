<?php

namespace App\Http\Middleware;

use App\Models\Promotion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        // Track referral token from URL
        if ($request->has('ref')) {
            $promotion = Promotion::where('referral_token', $request->ref)
                ->where('status', 'active')
                ->first();

            if ($promotion) {
                session([
                    'referral_token'   => $request->ref,
                    'profile_owner_id' => $promotion->profile_owner_id,
                    'referral_expires' => now()->addHours(config('commerce.referral_window_hours')),
                ]);
            }
        }

        // Clear expired referral session
        if (session('referral_expires') && now()->isAfter(session('referral_expires'))) {
            session()->forget(['referral_token', 'profile_owner_id', 'referral_expires']);
        }

        return $next($request);
    }
}