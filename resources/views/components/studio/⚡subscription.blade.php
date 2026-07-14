<?php

use App\Models\StudioSubscription;
use App\Services\PriceSuggestionService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public array $suggestions = [];
    public ?StudioSubscription $currentSubscription = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->currentSubscription = $user->studioSubscription;

        $service = new PriceSuggestionService();
        $this->suggestions = $service->getSuggestions($user);
    }

    public function subscribe(string $plan): void
    {
        $user = Auth::user();

        $prices = [
            'basic' => ['amount' => 999,  'name' => 'Studio Basic'],
            'pro'   => ['amount' => 1999, 'name' => 'Studio Pro'],
        ];

        if (!isset($prices[$plan])) return;

        $session = $user->checkout([
            [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $prices[$plan]['amount'],
                    'recurring'    => ['interval' => 'month'],
                    'product_data' => [
                        'name'        => 'Taongaf ' . $prices[$plan]['name'],
                        'description' => 'Monthly studio subscription',
                    ],
                ],
                'quantity' => 1,
            ],
        ], [
'success_url' => route('studio.success') . '?session_id={CHECKOUT_SESSION_ID}',
'cancel_url'  => route('studio.subscription'),
            'metadata'    => [
                'user_id' => $user->id,
                'plan'    => $plan,
                'type'    => 'studio_subscription',
            ],
            'mode' => 'subscription',
        ]);

        $this->redirect($session->url);
    }

    public function cancel(): void
    {
        if (!$this->currentSubscription) return;

        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        $stripe->subscriptions->cancel(
            $this->currentSubscription->stripe_subscription_id
        );

        $this->currentSubscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        session()->flash('message', 'Subscription cancelled.');
        $this->redirect(route('studio.subscription'));
    }

    public function with(): array
    {
        return [
            'suggestedPlan' => $this->suggestions['studio_plan']['value'] ?? 'basic',
            'planReason'    => $this->suggestions['studio_plan']['reason'] ?? '',
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="mb-2">Studio subscription</flux:heading>
        <p class="text-zinc-500 dark:text-zinc-400">
            Unlock your publishing studio and start building your community.
        </p>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Current subscription --}}
    @if($currentSubscription && $currentSubscription->isActive())
        <div class="p-6 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-xl mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-700 dark:text-green-400">
                        Active — {{ ucfirst($currentSubscription->plan) }} plan
                    </p>
                    <p class="text-xs text-green-600 mt-1">
                        Renews {{ $currentSubscription->current_period_ends_at?->format('M d, Y') }}
                    </p>
                </div>
                <flux:button wire:click="cancel" wire:confirm="Cancel your studio subscription?" variant="ghost" size="sm">
                    Cancel subscription
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Plan suggestion --}}
    @if($planReason)
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl mb-8">
            <div class="flex items-start gap-3">
                <i class="ti ti-bulb text-blue-500 mt-0.5" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-400">
                        We suggest the {{ ucfirst($suggestedPlan) }} plan for you
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-500 mt-0.5">
                        {{ $planReason }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Plans --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">

        {{-- Basic --}}
        <div class="border {{ $suggestedPlan === 'basic' ? 'border-zinc-900 dark:border-white' : 'border-zinc-200 dark:border-zinc-700' }} rounded-xl p-6">
            @if($suggestedPlan === 'basic')
                <span class="inline-block text-xs px-2 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full mb-3">
                    Suggested for you
                </span>
            @endif

            <p class="text-lg font-medium text-zinc-900 dark:text-white mb-1">Basic</p>
            <p class="text-3xl font-medium text-zinc-900 dark:text-white mb-1">
                $9.99<span class="text-sm font-normal text-zinc-400">/month</span>
            </p>
            <p class="text-xs text-zinc-400 mb-6">Perfect for solo publishers getting started</p>

            <ul class="space-y-2 mb-6">
                @foreach([
                    'Studio dashboard',
                    'Community management',
                    'Up to 3 staff members',
                    'Basic analytics',
                    'Surveys (up to 3 active)',
                    'Open & closed community',
                ] as $feature)
                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <i class="ti ti-check text-green-500 text-sm" aria-hidden="true"></i>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>

            @if(!$currentSubscription || !$currentSubscription->isActive())
                <flux:button
                    wire:click="subscribe('basic')"
                    wire:loading.attr="disabled"
                    variant="{{ $suggestedPlan === 'basic' ? 'primary' : 'outline' }}"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="subscribe">Get Basic</span>
                    <span wire:loading wire:target="subscribe">Redirecting...</span>
                </flux:button>
            @endif
        </div>

        {{-- Pro --}}
        <div class="border {{ $suggestedPlan === 'pro' ? 'border-zinc-900 dark:border-white' : 'border-zinc-200 dark:border-zinc-700' }} rounded-xl p-6">
            @if($suggestedPlan === 'pro')
                <span class="inline-block text-xs px-2 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full mb-3">
                    Suggested for you
                </span>
            @endif

            <p class="text-lg font-medium text-zinc-900 dark:text-white mb-1">Pro</p>
            <p class="text-3xl font-medium text-zinc-900 dark:text-white mb-1">
                $19.99<span class="text-sm font-normal text-zinc-400">/month</span>
            </p>
            <p class="text-xs text-zinc-400 mb-6">For established publishers with growing communities</p>

            <ul class="space-y-2 mb-6">
                @foreach([
                    'Everything in Basic',
                    'Unlimited staff members',
                    'Advanced analytics',
                    'Unlimited surveys',
                    'Subscription community',
                    'Price suggestion engine',
                    'Priority support',
                ] as $feature)
                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <i class="ti ti-check text-green-500 text-sm" aria-hidden="true"></i>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>

            @if(!$currentSubscription || !$currentSubscription->isActive())
                <flux:button
                    wire:click="subscribe('pro')"
                    wire:loading.attr="disabled"
                    variant="{{ $suggestedPlan === 'pro' ? 'primary' : 'outline' }}"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="subscribe">Get Pro</span>
                    <span wire:loading wire:target="subscribe">Redirecting...</span>
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Price cap breakdown --}}
    @if(isset($suggestions['price_cap']))
        <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-6">
            <p class="text-sm font-medium text-zinc-900 dark:text-white mb-4">
                Your community price cap
            </p>
            <div class="flex items-center justify-between mb-3">
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ $suggestions['price_cap']['value'] }} tokens/month max
                </p>
            </div>
            <p class="text-xs text-zinc-400 mb-4">
                Your cap grows as your audience and engagement grow.
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($suggestions['price_cap']['factors'] as $factor => $value)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                        <p class="text-xs text-zinc-400 capitalize mb-1">{{ $factor }}</p>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">+{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>