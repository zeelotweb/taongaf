<?php

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\TokenTransaction;

new class extends Component {
    use WithPagination;

    public string $filter = 'all';

public function with(): array
{
    $user   = Auth::user();
    $wallet = $user->wallet;

    return [
        'wallet'       => $wallet,
        'hasWallet'    => $wallet !== null,
        'transactions' => $wallet
            ? TokenTransaction::where('user_id', $user->id)
                ->when($this->filter === 'credits', fn($q) => $q->where('direction', 'credit'))
                ->when($this->filter === 'debits', fn($q) => $q->where('direction', 'debit'))
                ->latest()
                ->paginate(15)
            : collect(),
        'totalEarned'  => $wallet?->total_earned ?? 0,
        'totalSpent'   => $wallet?->total_spent ?? 0,
    ];
}

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <flux:heading size="xl">Wallet</flux:heading>
        <a href="{{ route('tokens.index') }}">
            <flux:button variant="primary" icon="plus">
                Buy tokens
            </flux:button>
        </a>
    </div>

{{-- No wallet yet --}}
@if(!$hasWallet)
    <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl mb-8">
        <i class="ti ti-coin text-4xl text-zinc-200 dark:text-zinc-700 mb-4 block" aria-hidden="true"></i>
        <p class="text-zinc-600 dark:text-zinc-400 mb-2 font-medium">No wallet yet</p>
        <p class="text-sm text-zinc-400 mb-6">
            Buy your first tokens to get started reading premium content.
        </p>
        <a href="{{ route('tokens.index') }}">
            <flux:button variant="primary" icon="plus">
                Buy your first tokens
            </flux:button>
        </a>
    </div>
@else
    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Token balance</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ number_format($wallet->token_balance) }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">≈ ${{ number_format($wallet->token_balance, 2) }} USD</p>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Total earned</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ number_format($totalEarned) }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">tokens from content</p>
        </div>

        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Total spent</p>
            <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                {{ number_format($totalSpent) }}
            </p>
            <p class="text-xs text-zinc-400 mt-1">tokens on content</p>
        </div>
    </div>

    {{-- Earnings balance if publisher --}}
    @if($wallet->earnings_balance > 0)
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-xl mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs text-green-600 uppercase tracking-wider mb-1">Earnings balance</p>
                <p class="text-2xl font-medium text-green-700 dark:text-green-400">
                    {{ number_format($wallet->earnings_balance) }} tokens
                </p>
                <p class="text-xs text-green-600 mt-1">
                    ≈ ${{ number_format($wallet->earnings_balance, 2) }} USD available to withdraw
                </p>
            </div>
            <flux:button variant="outline">
                Withdraw earnings
            </flux:button>
        </div>
    @endif
@endif

    {{-- Transaction history --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                Transaction history
            </p>
            <div class="flex gap-2">
                @foreach(['all', 'credits', 'debits'] as $option)
                    <button
                        wire:click="$set('filter', '{{ $option }}')"
                        class="text-xs px-3 py-1 rounded-full transition-colors
                            {{ $filter === $option
                                ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                                : 'text-zinc-400 hover:text-zinc-600' }}"
                    >
                        {{ ucfirst($option) }}
                    </button>
                @endforeach
            </div>
        </div>

        @if($transactions->isEmpty())
            <div class="text-center py-12 border border-zinc-100 dark:border-zinc-800 rounded-xl">
                <i class="ti ti-receipt text-3xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
                <p class="text-sm text-zinc-400">No transactions yet.</p>
            </div>
        @else
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                @foreach($transactions as $transaction)
                    <div class="flex items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                {{ $transaction->isCredit()
                                    ? 'bg-green-50 dark:bg-green-900/20'
                                    : 'bg-red-50 dark:bg-red-900/20' }}">
                                <i class="ti {{ $transaction->isCredit() ? 'ti-arrow-down text-green-500' : 'ti-arrow-up text-red-500' }} text-sm"
                                   aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $transaction->description ?? ucfirst($transaction->type) }}
                                </p>
                                <p class="text-xs text-zinc-400">
                                    {{ $transaction->created_at->format('M d, Y · h:i A') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium
                                {{ $transaction->isCredit()
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->isCredit() ? '+' : '-' }}{{ number_format($transaction->amount) }}
                                <span class="text-xs font-normal">tokens</span>
                            </p>
                            <p class="text-xs text-zinc-400">
                                Balance: {{ number_format($transaction->balance_after) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>