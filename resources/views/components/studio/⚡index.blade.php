<?php

use App\Models\PublisherMetrics;
use App\Models\StudioMembership;
use App\Models\CommunityMember;
use App\Services\PriceSuggestionService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public function with(): array
    {
        $user         = Auth::user();
        $metrics      = $user->publisherMetrics;
        $subscription = $user->studioSubscription;
        $service      = new PriceSuggestionService();
        $suggestions  = $metrics ? $service->getSuggestions($user) : [];

        return [
            'user'         => $user,
            'metrics'      => $metrics,
            'subscription' => $subscription,
            'suggestions'  => $suggestions,
            'staffCount'   => StudioMembership::where('publisher_id', $user->id)
                ->where('status', 'active')->count(),
            'memberCount'  => CommunityMember::where('publisher_id', $user->id)
                ->where('status', 'active')->count(),
            'recentStaff'  => StudioMembership::where('publisher_id', $user->id)
                ->with('user')
                ->latest()
                ->take(3)
                ->get(),
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">My studio</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                {{ ucfirst($subscription?->plan) }} plan
                · renews {{ $subscription?->current_period_ends_at?->format('M d, Y') }}
            </p>
        </div>
        <a href="{{ route('studio.subscription') }}">
            <flux:button variant="outline" size="sm">
                Manage subscription
            </flux:button>
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid sm:grid-cols-4 gap-4">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Content</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ $metrics?->content_count ?? 0 }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">published pieces</p>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Community</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ $memberCount }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">active members</p>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Engagement</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ number_format(($metrics?->engagement_rate ?? 0) * 100, 1) }}%
            </p>
            <p class="text-xs text-zinc-400 mt-1">engagement rate</p>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Earnings</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ number_format($metrics?->monthly_token_earnings ?? 0) }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">tokens this month</p>
        </div>
    </div>

    {{-- Price suggestion --}}
    @if(!empty($suggestions))
        <div class="p-5 border border-blue-100 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-400 mb-1">
                        Suggested community price
                    </p>
                    <p class="text-2xl font-medium text-blue-800 dark:text-blue-300">
                        {{ $suggestions['community_price']['value'] ?? 0 }} tokens/month
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-500 mt-1">
                        {{ $suggestions['community_price']['reason'] ?? '' }}
                    </p>
                    <p class="text-xs text-blue-500 dark:text-blue-600 mt-1">
                        Your cap: {{ $suggestions['price_cap']['value'] ?? 0 }} tokens/month
                    </p>
                </div>
                <a href="{{ route('studio.community') }}">
                    <flux:button size="sm" variant="outline">
                        Set price
                    </flux:button>
                </a>
            </div>
        </div>
    @endif

    {{-- Quick links --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('studio.staff') }}"
           class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
            <i class="ti ti-users text-zinc-400 text-xl" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Staff</p>
                <p class="text-xs text-zinc-400">{{ $staffCount }} active</p>
            </div>
        </a>

        <a href="{{ route('studio.community') }}"
           class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
            <i class="ti ti-heart text-zinc-400 text-xl" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Community</p>
                <p class="text-xs text-zinc-400">{{ $memberCount }} members</p>
            </div>
        </a>

        <a href="{{ route('studio.analytics') }}"
           class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
            <i class="ti ti-chart-bar text-zinc-400 text-xl" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Analytics</p>
                <p class="text-xs text-zinc-400">View performance</p>
            </div>
        </a>

        <a href="{{ route('studio.surveys') }}"
           class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
            <i class="ti ti-clipboard text-zinc-400 text-xl" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Surveys</p>
                <p class="text-xs text-zinc-400">Engage your community</p>
            </div>
        </a>
    </div>

    {{-- Recent staff --}}
    @if($recentStaff->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Recent staff</p>
                <a href="{{ route('studio.staff') }}" class="text-xs text-zinc-400 hover:text-zinc-600">
                    View all →
                </a>
            </div>
            <div class="space-y-2">
                @foreach($recentStaff as $membership)
                    <div class="flex items-center justify-between p-3 border border-zinc-100 dark:border-zinc-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                <span class="text-xs font-medium text-zinc-500">
                                    {{ strtoupper(substr($membership->user->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $membership->user->name }}
                                </p>
                                <p class="text-xs text-zinc-400">
                                    {{ implode(', ', array_map(fn($r) => ucfirst(str_replace('_', ' ', $r)), $membership->roles ?? [])) }}
                                </p>
                            </div>
                        </div>
                        <flux:badge size="sm" :color="$membership->status === 'active' ? 'green' : 'yellow'">
                            {{ ucfirst($membership->status) }}
                        </flux:badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>