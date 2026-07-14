<?php

use App\Models\ProfileCommerceSetting;
use App\Models\Promotion;
use App\Models\PublisherMetrics;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public bool $isEnabled = false;
    public array $allowedServices = [];
    public int $promotionFee = 0;
    public bool $autoApprove = false;
    public bool $isUnlocked = false;

    const SERVICES = [
        'promotion'  => 'Content promotion — flat fee to feature content',
        'sale_refer' => 'Sales referral — commission on sales made through your profile',
    ];

    public function mount(): void
    {
        $user     = Auth::user();
        $settings = $user->profileCommerceSetting;

        $this->isUnlocked = $user->isEligibleForCommerce();

        if ($settings) {
            $this->isEnabled      = $settings->is_enabled;
            $this->allowedServices = $settings->allowed_services ?? [];
            $this->promotionFee   = $settings->promotion_fee;
            $this->autoApprove    = $settings->auto_approve;
        }
    }

    public function save(): void
    {
        $this->validate([
            'allowedServices' => 'array',
            'promotionFee'    => 'integer|min:0',
        ]);

        ProfileCommerceSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'is_enabled'       => $this->isEnabled,
                'allowed_services' => $this->allowedServices,
                'promotion_fee'    => $this->promotionFee,
                'auto_approve'     => $this->autoApprove,
                'is_unlocked'      => $this->isUnlocked,
                'unlocked_at'      => $this->isUnlocked ? now() : null,
            ]
        );

        session()->flash('message', 'Commerce settings saved.');
    }

    public function approve(int $promotionId): void
    {
        Promotion::where('id', $promotionId)
            ->where('profile_owner_id', Auth::id())
            ->update([
                'status'      => 'active',
                'approved_at' => now(),
            ]);
    }

    public function reject(int $promotionId): void
    {
        Promotion::where('id', $promotionId)
            ->where('profile_owner_id', Auth::id())
            ->update([
                'status'      => 'rejected',
                'rejected_at' => now(),
            ]);
    }

    public function with(): array
    {
        $user    = Auth::user();
        $metrics = $user->publisherMetrics;

        return [
            'metrics'            => $metrics,
            'pendingPromotions'  => Promotion::where('profile_owner_id', $user->id)
                ->where('status', 'pending')
                ->with(['hustler', 'promotable'])
                ->latest()
                ->get(),
            'activePromotions'   => Promotion::where('profile_owner_id', $user->id)
                ->where('status', 'active')
                ->with(['hustler', 'promotable'])
                ->latest()
                ->get(),
            'totalEarned'        => Promotion::where('profile_owner_id', $user->id)
                ->sum('profile_owner_earned'),
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div>
        <a href="{{ route('studio.index') }}" wire:navigate
           class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
            ← Back to studio
        </a>
        <flux:heading size="xl">Profile commerce</flux:heading>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            Let others promote and sell through your community.
        </p>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Not unlocked --}}
    @if(!$isUnlocked)
        <div class="text-center py-16 border border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
            <i class="ti ti-lock text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2">
                Profile commerce not yet unlocked
            </p>
            <p class="text-xs text-zinc-400 mb-4">
                Reach {{ number_format(config('commerce.profile_commerce_unlock.min_followers')) }} followers
                or earn {{ number_format(config('commerce.profile_commerce_unlock.min_earnings')) }} tokens
                to unlock profile commerce.
            </p>
            @if($metrics)
                <div class="grid sm:grid-cols-2 gap-3 max-w-xs mx-auto text-left mt-4">
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <p class="text-xs text-zinc-400">Followers</p>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ number_format($metrics->follower_count) }}
                            / {{ number_format(config('commerce.profile_commerce_unlock.min_followers')) }}
                        </p>
                    </div>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <p class="text-xs text-zinc-400">Tokens earned</p>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ number_format($metrics->total_token_earnings) }}
                            / {{ number_format(config('commerce.profile_commerce_unlock.min_earnings')) }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    @else

        {{-- Earnings summary --}}
        <div class="p-5 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-xl">
            <p class="text-xs text-green-600 uppercase tracking-wider mb-1">Total commerce earnings</p>
            <p class="text-2xl font-medium text-green-700 dark:text-green-400">
                {{ number_format($totalEarned) }} tokens
            </p>
            <p class="text-xs text-green-600 mt-1">Earned passively from your community</p>
        </div>

        {{-- Settings --}}
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 space-y-5">
            <p class="text-sm font-medium text-zinc-900 dark:text-white">Commerce settings</p>

            {{-- Enable toggle --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-900 dark:text-white">Enable profile commerce</p>
                    <p class="text-xs text-zinc-400 mt-0.5">
                        Allow others to promote and sell through your community
                    </p>
                </div>
                <flux:checkbox wire:model.live="isEnabled" />
            </div>

            @if($isEnabled)
                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-5 space-y-4">

                    {{-- Allowed services --}}
                    <div>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-3">Allowed services</p>
                        @foreach(self::SERVICES as $service => $description)
                            <label class="flex items-start gap-3 mb-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="allowedServices"
                                    value="{{ $service }}"
                                    class="rounded border-zinc-300 mt-0.5"
                                />
                                <div>
                                    <p class="text-sm text-zinc-900 dark:text-white">
                                        {{ ucfirst(str_replace('_', ' ', $service)) }}
                                    </p>
                                    <p class="text-xs text-zinc-400">{{ $description }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    {{-- Flat promotion fee --}}
                    @if(in_array('promotion', $allowedServices))
                        <flux:field>
                            <flux:label>Flat promotion fee (tokens)</flux:label>
                            <flux:input
                                type="number"
                                wire:model="promotionFee"
                                min="0"
                                placeholder="0 = free to promote"
                                class="w-48"
                            />
                            <p class="text-xs text-zinc-400 mt-1">
                                One-time fee paid by the hustler to promote on your profile
                            </p>
                        </flux:field>
                    @endif

                    {{-- Auto approve --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-900 dark:text-white">Auto approve promotions</p>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                Automatically approve all promotion requests
                            </p>
                        </div>
                        <flux:checkbox wire:model="autoApprove" />
                    </div>
                </div>
            @endif

            <div class="flex justify-end pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button wire:click="save" variant="primary">
                    Save settings
                </flux:button>
            </div>
        </div>

        {{-- Pending promotions --}}
        @if($pendingPromotions->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Pending approval ({{ $pendingPromotions->count() }})
                </p>
                <div class="space-y-3">
                    @foreach($pendingPromotions as $promotion)
                        <div class="border border-amber-100 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $promotion->hustler->name }}
                                        wants to promote
                                        <span class="text-zinc-500">{{ $promotion->promotable?->title }}</span>
                                    </p>
                                    <p class="text-xs text-zinc-400 mt-1">
                                        Service: {{ ucfirst(str_replace('_', ' ', $promotion->service_type)) }}
                                        · Submitted {{ $promotion->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <flux:button
                                        wire:click="approve({{ $promotion->id }})"
                                        size="sm"
                                        variant="primary"
                                    >
                                        Approve
                                    </flux:button>
                                    <flux:button
                                        wire:click="reject({{ $promotion->id }})"
                                        size="sm"
                                        variant="ghost"
                                    >
                                        Reject
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Active promotions --}}
        @if($activePromotions->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Active promotions ({{ $activePromotions->count() }})
                </p>
                <div class="space-y-2">
                    @foreach($activePromotions as $promotion)
                        <div class="flex items-center justify-between p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl">
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $promotion->promotable?->title }}
                                </p>
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    By {{ $promotion->hustler->name }}
                                    · {{ $promotion->total_sales_tokens }} tokens in sales
                                    · You earned {{ $promotion->profile_owner_earned }} tokens
                                </p>
                            </div>
                            <flux:badge size="sm" color="green">Active</flux:badge>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

</div>