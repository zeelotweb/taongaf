<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public int $customTokens = 10;
    public string $selectedPackage = 'standard';

    public string $returnUrl = '';

public function mount(): void
{
    $this->packages  = config('platform.token_packages') ?? [];
    $this->returnUrl = url()->previous() !== url()->current() 
        ? url()->previous() 
        : route('home');
}

    public function with(): array
    {
        return [
            'packages' => config('platform.token_packages'),
            'wallet'   => Auth::user()->wallet,
        ];
    }


    public function selectPackage(string $name): void
    {
        $this->selectedPackage = strtolower($name);
    }

    public function calculateCustomPrice(): float
    {
        $stripePercentage = config('platform.stripe_percentage') / 100;
        $stripeFixed      = config('platform.stripe_fixed');
        $tokenToUsd       = config('platform.token_to_usd');

        $faceValue = $this->customTokens * $tokenToUsd;
        return round(($faceValue + $stripeFixed) / (1 - $stripePercentage), 2);
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">



    {{-- Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <flux:heading size="xl">Buy tokens</flux:heading>
        <p class="text-zinc-500 dark:text-zinc-400 mt-1">
            Use tokens to access premium content, chapters and editorials.
        </p>
    </div>
    <a href="{{ $returnUrl }}"
       class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors">
        ← Back
    </a>
</div>


    {{-- Current balance --}}
    @if($wallet)
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Current balance</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($wallet->token_balance) }} tokens
                </p>
            </div>
            <i class="ti ti-coin text-3xl text-zinc-300" aria-hidden="true"></i>
        </div>
    @endif

    {{-- Fixed packages --}}
    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">Choose a package</p>

    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        @foreach($packages as $package)
            <div
                wire:click="selectPackage('{{ $package['name'] }}')"
                class="relative border rounded-xl p-5 cursor-pointer transition-all
                    {{ strtolower($package['name']) === $selectedPackage
                        ? 'border-zinc-900 dark:border-white bg-zinc-50 dark:bg-zinc-800'
                        : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' }}"
            >
                @if(isset($package['popular']) && $package['popular'])
                    <span class="absolute -top-2.5 left-4 text-xs px-2 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full">
                        Most popular
                    </span>
                @endif

                <p class="text-lg font-medium text-zinc-900 dark:text-white mb-1">
                    {{ $package['name'] }}
                </p>
                <p class="text-3xl font-medium text-zinc-900 dark:text-white mb-1">
                    {{ $package['tokens'] }}
                    <span class="text-sm font-normal text-zinc-400">tokens</span>
                </p>
                <p class="text-sm text-zinc-500 mb-3">${{ number_format($package['price_usd'], 2) }}</p>
                <p class="text-xs text-zinc-400">{{ $package['description'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Custom amount --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 mb-8">
        <p class="text-sm font-medium text-zinc-900 dark:text-white mb-4">Custom amount</p>

        <div class="flex items-center gap-4 mb-4">
            <flux:input
                type="number"
                wire:model.live="customTokens"
                min="1"
                max="10000"
                class="w-32"
            />
            <span class="text-sm text-zinc-500">tokens</span>
            <span class="text-sm text-zinc-400">→</span>
            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                ${{ number_format($this->calculateCustomPrice(), 2) }} USD
            </span>
        </div>

        <p class="text-xs text-zinc-400">
            Price includes Stripe processing fee. 1 token = $1.00 USD.
        </p>
    </div>

    {{-- Checkout --}}
    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-6">

        {{-- Selected package checkout --}}
        @foreach($packages as $package)
            @if(strtolower($package['name']) === $selectedPackage)
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $package['name'] }} — {{ $package['tokens'] }} tokens
                        </p>
                        <p class="text-xs text-zinc-400">${{ number_format($package['price_usd'], 2) }} USD</p>
                    </div>
                    <form action="{{ route('tokens.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tokens" value="{{ $package['tokens'] }}">
                        <input type="hidden" name="price_usd" value="{{ $package['price_usd'] }}">
                        <flux:button type="submit" variant="primary">
                            Buy {{ $package['tokens'] }} tokens
                        </flux:button>
                    </form>
                </div>
            @endif
        @endforeach

        {{-- Custom checkout --}}
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                    Custom — {{ $customTokens }} tokens
                </p>
                <p class="text-xs text-zinc-400">${{ number_format($this->calculateCustomPrice(), 2) }} USD</p>
            </div>
            <form action="{{ route('tokens.checkout') }}" method="POST">
                @csrf
                <input type="hidden" name="tokens" value="{{ $customTokens }}">
                <input type="hidden" name="price_usd" value="{{ $this->calculateCustomPrice() }}">
                <flux:button type="submit" variant="outline">
                    Buy {{ $customTokens }} tokens
                </flux:button>
            </form>
        </div>

        <p class="text-xs text-zinc-400 mt-4">
            Payments processed securely by Stripe. Tokens are non-refundable.
        </p>
    </div>

</div>