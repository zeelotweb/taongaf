<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchase;
use App\Models\Editorial;
use App\Models\Book;

new class extends Component {

    public function with(): array
    {
        $user   = Auth::user();
        $wallet = $user->wallet;

        // Recent purchases
        $recentPurchases = Purchase::where('user_id', $user->id)
            ->with('purchasable')
            ->latest()
            ->take(5)
            ->get();

        // Continue reading — last purchased content
        $continueReading = Purchase::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('purchasable')
            ->latest()
            ->take(3)
            ->get();

        return [
            'wallet'          => $wallet,
            'recentPurchases' => $recentPurchases,
            'continueReading' => $continueReading,
            'isPublisher'     => in_array($user->role, ['superadmin', 'admin', 'publisher']),
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Welcome --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-zinc-900 dark:text-white">
                Welcome back, {{ Auth::user()->name }} 👋
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Here's what's happening on your account.
            </p>
        </div>
        @can('access-admin')
            <a href="{{ route('admin.dashboard') }}">
                <flux:button variant="outline" icon="cog">
                    Admin panel
                </flux:button>
            </a>
        @endcan
    </div>


<a href="{{ route('search') }}"
   class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors m-4">
    <flux:icon.magnifying-glass class="size-5" />
</a>

<a href="{{ route('studio.commerce') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-shopping-bag text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">Commerce</p>
        <p class="text-xs text-zinc-400">Monetize your community</p>
    </div>
</a>

<a href="{{ route('messages.inbox') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-message-circle text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">Messages</p>
        <p class="text-xs text-zinc-400">Your inbox</p>
    </div>
</a>

<a href="{{ route('messages.chat-rooms') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-messages text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">Chat rooms</p>
        <p class="text-xs text-zinc-400">Group conversations</p>
    </div>
</a>
@if(auth()->user()->isEligibleToHustle())
    <a href="{{ route('hustle.index') }}"
       class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
        <i class="ti ti-rocket text-zinc-400 text-xl" aria-hidden="true"></i>
        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-white">Hustle</p>
            <p class="text-xs text-zinc-400">Promote and earn commissions</p>
        </div>
    </a>
@endif
@if(auth()->user()->hasActiveStudio())
    <a href="{{ route('studio.index') }}"
       class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
        <i class="ti ti-building-store text-zinc-400 text-xl" aria-hidden="true"></i>
        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-white">My studio</p>
            <p class="text-xs text-zinc-400">Manage your publishing studio</p>
        </div>
    </a>

@else
    <a href="{{ route('studio.subscription') }}"
       class="flex items-center gap-3 p-4 border border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-zinc-300 transition-colors">
        <i class="ti ti-building-store text-zinc-300 text-xl" aria-hidden="true"></i>
        <div>
            <p class="text-sm font-medium text-zinc-500">Unlock your studio</p>
            <p class="text-xs text-zinc-400">Start from $9.99/month</p>
        </div>
    </a>
@endif
<a href="{{ route('publish.editorials.index') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-edit text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">My editorials</p>
        <p class="text-xs text-zinc-400">Manage your published work</p>
    </div>
</a>
<a href="{{ route('publish.books.index') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-books text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">My books</p>
        <p class="text-xs text-zinc-400">Manage your books</p>
    </div>
</a>
<a href="{{ route('bookmarks') }}"
   class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
    <i class="ti ti-bookmark text-zinc-400 text-xl" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-medium text-zinc-900 dark:text-white">Bookmarks</p>
        <p class="text-xs text-zinc-400">Your saved content</p>
    </div>
</a>



    {{-- Wallet card --}}
    <div class="grid sm:grid-cols-3 gap-4">

        {{-- Token balance --}}
        @if($wallet)
            <div class="p-5 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Token balance</p>
                <p class="text-3xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($wallet->token_balance) }}
                </p>
                <p class="text-xs text-zinc-400 mt-1 mb-3">≈ ${{ number_format($wallet->token_balance, 2) }} USD</p>
                <a href="{{ route('tokens.index') }}">
                    <flux:button size="sm" variant="outline" icon="plus">Buy tokens</flux:button>
                </a>
            </div>

            {{-- Earnings --}}
            @if($isPublisher)
                <div class="p-5 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-100 dark:border-green-800">
                    <p class="text-xs text-green-600 uppercase tracking-wider mb-1">Earnings</p>
                    <p class="text-3xl font-medium text-green-700 dark:text-green-400">
                        {{ number_format($wallet->earnings_balance) }}
                    </p>
                    <p class="text-xs text-green-600 mt-1 mb-3">tokens available</p>
                    <flux:button size="sm" variant="outline">Withdraw</flux:button>
                </div>
            @endif

            {{-- Wallet link --}}
            <div class="p-5 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col justify-between">
                <div>
                    <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Total spent</p>
                    <p class="text-3xl font-medium text-zinc-900 dark:text-white">
                        {{ number_format($wallet->total_spent) }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">tokens on content</p>
                </div>
                <a href="{{ route('wallet.index') }}" class="mt-3 text-xs text-zinc-400 hover:text-zinc-600 transition-colors">
                    View full wallet →
                </a>
            </div>
        @else
            {{-- No wallet yet --}}
            <div class="sm:col-span-3 p-6 border border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl text-center">
                <i class="ti ti-coin text-3xl text-zinc-300 mb-3 block" aria-hidden="true"></i>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">No wallet yet</p>
                <p class="text-xs text-zinc-400 mb-4">Buy tokens to access premium content.</p>
                <a href="{{ route('tokens.index') }}">
                    <flux:button variant="primary" icon="plus">Buy your first tokens</flux:button>
                </a>
            </div>
        @endif
    </div>

    {{-- Continue reading --}}
    @if($continueReading->isNotEmpty())
        <div>
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">Continue reading</p>
            <div class="space-y-2">
                @foreach($continueReading as $purchase)
                    @if($purchase->purchasable)
                        @php $content = $purchase->purchasable; @endphp
                        <div class="flex items-center justify-between p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                            <div class="flex items-center gap-3">
                                {{-- Cover --}}
                                <div class="w-12 h-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
                                    @if($content->cover_image)
                                        <img
                                            src="{{ Storage::url($content->cover_image) }}"
                                            alt="{{ $content->title }}"
                                            class="w-full h-full object-cover"
                                        />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="ti ti-file-text text-zinc-300 text-sm" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $content->title }}
                                    </p>
                                    <p class="text-xs text-zinc-400">
                                        {{ class_basename($purchase->purchasable_type) }}
                                        · {{ $purchase->tokens_spent }} tokens
                                    </p>
                                </div>
                            </div>
                            @php
                                $route = match(class_basename($purchase->purchasable_type)) {
                                    'Editorial' => route('editorial', $content->slug),
                                    'Book'      => route('book', $content->slug),
                                    default     => '#',
                                };
                            @endphp
                            <a href="{{ $route }}"
                               class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Continue →
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quick links --}}
    <div>
        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">Explore</p>
        <div class="grid sm:grid-cols-2 gap-3">
            <a href="{{ route('editorials') }}"
               class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                <i class="ti ti-file-text text-zinc-400 text-xl" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Editorials</p>
                    <p class="text-xs text-zinc-400">Browse all editorials</p>
                </div>
            </a>
            <a href="{{ route('books') }}"
               class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                <i class="ti ti-book text-zinc-400 text-xl" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Books</p>
                    <p class="text-xs text-zinc-400">Browse the library</p>
                </div>
            </a>
            <a href="{{ route('wallet.index') }}"
               class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                <i class="ti ti-coin text-zinc-400 text-xl" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Wallet</p>
                    <p class="text-xs text-zinc-400">Tokens & transactions</p>
                </div>
            </a>
            <a href="{{ route('tokens.index') }}"
               class="flex items-center gap-3 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                <i class="ti ti-plus text-zinc-400 text-xl" aria-hidden="true"></i>
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Buy tokens</p>
                    <p class="text-xs text-zinc-400">Top up your balance</p>
                </div>
            </a>
        </div>
    </div>

</div>