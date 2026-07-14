<?php

use App\Models\ProfileCommerceSetting;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $service = '';

    public function with(): array
    {
        $user = Auth::user();

        return [
            'isEligible' => $user->isEligibleToHustle(),
            'profiles'   => User::whereHas('profileCommerceSetting', fn($q) =>
                $q->where('is_enabled', true)->where('is_unlocked', true)
            )
            ->with(['profileCommerceSetting', 'publisherMetrics'])
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->service, fn($q) => $q->whereHas('profileCommerceSetting', fn($s) =>
                $s->whereJsonContains('allowed_services', $this->service)
            ))
            ->where('id', '!=', $user->id)
            ->latest()
            ->paginate(12),
            'myPromotions' => \App\Models\Promotion::where('hustler_id', $user->id)
                ->with(['profileOwner', 'promotable'])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Hustle</flux:heading>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            Promote content through other publishers' communities and earn commission.
        </p>
    </div>

    {{-- Not eligible --}}
    @if(!$isEligible)
        <div class="p-6 border border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl text-center">
            <i class="ti ti-lock text-3xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">
                Hustle not yet unlocked
            </p>
            <p class="text-xs text-zinc-400">
                Earn {{ number_format(config('commerce.hustle_unlock.min_earnings')) }} tokens
                or reach {{ number_format(config('commerce.hustle_unlock.min_followers')) }} followers
                to unlock the hustle feature.
            </p>
        </div>
    @else

        {{-- My promotions summary --}}
        @if($myPromotions->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    My active promotions
                </p>
                <div class="space-y-2">
                    @foreach($myPromotions as $promotion)
                        <div class="flex items-center justify-between p-3 border border-zinc-100 dark:border-zinc-800 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $promotion->promotable?->title }}
                                </p>
                                <p class="text-xs text-zinc-400">
                                    On {{ $promotion->profileOwner->name }}'s profile
                                    · Earned {{ $promotion->hustler_commission_earned }} tokens
                                </p>
                            </div>
                            <flux:badge size="sm"
                                :color="$promotion->status === 'active' ? 'green' : ($promotion->status === 'pending' ? 'yellow' : 'zinc')">
                                {{ ucfirst($promotion->status) }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Browse profiles --}}
        <div>
            <div class="flex gap-3 mb-6">
                <flux:input
                    wire:model.live="search"
                    placeholder="Search profiles..."
                    icon="magnifying-glass"
                    class="flex-1"
                />
                <flux:select wire:model.live="service" class="w-48">
                    <flux:select.option value="">All services</flux:select.option>
                    <flux:select.option value="promotion">Promotion</flux:select.option>
                    <flux:select.option value="sale_refer">Sales referral</flux:select.option>
                </flux:select>
            </div>

            @if($profiles->isEmpty())
                <div class="text-center py-12 border border-zinc-100 dark:border-zinc-800 rounded-xl">
                    <p class="text-sm text-zinc-400">No profiles available for commerce yet.</p>
                </div>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($profiles as $profile)
                        <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-5 hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                            {{-- Profile --}}
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($profile->avatar_path)
                                        <img src="{{ Storage::url($profile->avatar_path) }}" class="w-full h-full object-cover" />
                                    @else
                                        <span class="text-sm font-medium text-zinc-500">
                                            {{ strtoupper(substr($profile->name, 0, 2)) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $profile->name }}
                                    </p>
                                    <p class="text-xs text-zinc-400">
                                        {{ number_format($profile->publisherMetrics?->follower_count ?? 0) }} followers
                                    </p>
                                </div>
                            </div>

                            {{-- Stats --}}
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                    <p class="text-xs text-zinc-400">Engagement</p>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ number_format(($profile->publisherMetrics?->engagement_rate ?? 0) * 100, 1) }}%
                                    </p>
                                </div>
                                <div class="p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                    <p class="text-xs text-zinc-400">Promo fee</p>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $profile->profileCommerceSetting?->promotion_fee ?? 0 }} tokens
                                    </p>
                                </div>
                            </div>

                            {{-- Services --}}
                            <div class="flex flex-wrap gap-1 mb-4">
                                @foreach($profile->profileCommerceSetting?->allowed_services ?? [] as $service)
                                    <flux:badge size="sm" color="zinc">
                                        {{ ucfirst(str_replace('_', ' ', $service)) }}
                                    </flux:badge>
                                @endforeach
                            </div>

                            <a href="{{ route('hustle.promote', $profile) }}">
                                <flux:button variant="outline" class="w-full" size="sm">
                                    Promote here
                                </flux:button>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">{{ $profiles->links() }}</div>
            @endif
        </div>
    @endif

</div>