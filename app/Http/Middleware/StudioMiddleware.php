<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudioMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Publisher owns the studio
        if ($user->hasActiveStudio()) {
            return $next($request);
        }

        // Staff member of any active studio
        $isStaff = \App\Models\StudioMembership::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($isStaff) {
            return $next($request);
        }

        abort(403, 'You do not have access to this studio.');
    }
}