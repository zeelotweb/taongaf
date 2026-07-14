<?php

use App\Models\CommunityMember;
use App\Models\PublisherMetrics;
use App\Services\PriceSuggestionService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $filter = 'all';
    public string $search = '';

    // Community settings
    public string $accessType = 'open';
    public int $subscriptionPrice = 0;
    public bool $showSettings = false;

    public function mount(): void
    {
        // Load current community settings from publisher profile
        $user = Auth::user();
        // We'll store these in publisher_metrics or a settings table later
        // For now default to open
        $this->accessType = 'open';
    }

    public function saveSettings(): void
    {
        $this->validate([
            'accessType'        => 'required|in:open,closed,subscription',
            'subscriptionPrice' => 'required_if:accessType,subscription|integer|min:1',
        ]);

        // Save to user model or settings — we'll wire this properly
        session()->flash('message', 'Community settings saved.');
        $this->showSettings = false;
    }

    public function approve(int $memberId): void
    {
        CommunityMember::where('id', $memberId)
            ->where('publisher_id', Auth::id())
            ->update(['status' => 'active']);
    }

    public function block(int $memberId, string $reason = ''): void
    {
        CommunityMember::where('id', $memberId)
            ->where('publisher_id', Auth::id())
            ->update([
                'status'       => 'blocked',
                'blocked_at'   => now(),
                'block_reason' => $reason,
            ]);
    }

    public function unblock(int $memberId): void
    {
        CommunityMember::where('id', $memberId)
            ->where('publisher_id', Auth::id())
            ->update([
                'status'       => 'active',
                'blocked_at'   => null,
                'block_reason' => null,
            ]);
    }

    public function remove(int $memberId): void
    {
        CommunityMember::where('id', $memberId)
            ->where('publisher_id', Auth::id())
            ->delete();
    }

    public function with(): array
    {
        $user    = Auth::user();
        $metrics = $user->publisherMetrics;

        $service     = new PriceSuggestionService();
        $suggestions = $metrics ? $service->getSuggestions($user) : [];

        return [
            'members' => CommunityMember::where('publisher_id', $user->id)
                ->with('user')
                ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
                ->when($this->search, fn($q) => $q->whereHas('user', fn($u) =>
                    $u->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                ))
                ->latest()
                ->paginate(15),
            'counts' => [
                'all'         => CommunityMember::where('publisher_id', $user->id)->count(),
                'active'      => CommunityMember::where('publisher_id', $user->id)->where('status', 'active')->count(),
                'pending'     => CommunityMember::where('publisher_id', $user->id)->where('status', 'pending')->count(),
                'blocked'     => CommunityMember::where('publisher_id', $user->id)->where('status', 'blocked')->count(),
                'subscribed'  => CommunityMember::where('publisher_id', $user->id)->where('type', 'subscribed')->where('status', 'active')->count(),
            ],
            'suggestions' => $suggestions,
            'priceCap'    => $suggestions['price_cap']['value'] ?? 10,
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('studio.index') }}" wire:navigate
               class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
                ← Back to studio
            </a>
            <flux:heading size="xl">Community</flux:heading>
        </div>
        <flux:button
            wire:click="$set('showSettings', true)"
            variant="outline"
            icon="settings"
        >
            Community settings
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total', 'value' => $counts['all'], 'color' => 'zinc'],
            ['label' => 'Active', 'value' => $counts['active'], 'color' => 'green'],
            ['label' => 'Pending', 'value' => $counts['pending'], 'color' => 'yellow'],
            ['label' => 'Subscribers', 'value' => $counts['subscribed'], 'color' => 'blue'],
        ] as $stat)
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">{{ $stat['label'] }}</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Settings modal --}}
    @if($showSettings)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 space-y-4">
            <p class="text-sm font-medium text-zinc-900 dark:text-white">Community settings</p>

            <flux:field>
                <flux:label>Access type</flux:label>
                <flux:select wire:model.live="accessType">
                    <flux:select.option value="open">Open — anyone can join</flux:select.option>
                    <flux:select.option value="closed">Closed — requires approval</flux:select.option>
                    <flux:select.option value="subscription">Subscription — token based</flux:select.option>
                </flux:select>
            </flux:field>

            @if($accessType === 'subscription')
                <flux:field>
                    <flux:label>Monthly subscription price (tokens)</flux:label>
                    <div class="space-y-3">
                        <flux:input
                            type="number"
                            wire:model="subscriptionPrice"
                            min="1"
                            max="{{ $priceCap }}"
                            placeholder="e.g. 20"
                        />
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span>Min: 1 token</span>
                            <span>Your cap: {{ $priceCap }} tokens</span>
                        </div>

                        {{-- Price suggestion --}}
                        @if(!empty($suggestions['community_price']))
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg">
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    💡 Suggested price: <strong>{{ $suggestions['community_price']['value'] }} tokens/month</strong>
                                    — {{ $suggestions['community_price']['reason'] }}
                                </p>
                                <button
                                    wire:click="$set('subscriptionPrice', {{ $suggestions['community_price']['value'] }})"
                                    class="text-xs text-blue-600 underline mt-1"
                                >
                                    Use suggested price
                                </button>
                            </div>
                        @endif

                        {{-- Price cap breakdown --}}
                        @if(!empty($suggestions['price_cap']['factors']))
                            <details class="text-xs text-zinc-400">
                                <summary class="cursor-pointer hover:text-zinc-600">
                                    Why is my cap {{ $priceCap }} tokens?
                                </summary>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    @foreach($suggestions['price_cap']['factors'] as $factor => $value)
                                        <div class="p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                            <p class="capitalize">{{ $factor }}: +{{ $value }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                </flux:field>
            @endif

            <div class="flex gap-3">
                <flux:button wire:click="saveSettings" variant="primary">Save settings</flux:button>
                <flux:button wire:click="$set('showSettings', false)" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <flux:input
            wire:model.live="search"
            placeholder="Search members..."
            icon="magnifying-glass"
            class="flex-1"
        />
        <div class="flex gap-2">
            @foreach(['all', 'active', 'pending', 'blocked'] as $status)
                <button
                    wire:click="$set('filter', '{{ $status }}')"
                    class="text-xs px-3 py-1.5 rounded-full transition-colors
                        {{ $filter === $status
                            ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                            : 'border border-zinc-200 dark:border-zinc-700 text-zinc-400 hover:text-zinc-600' }}"
                >
                    {{ ucfirst($status) }}
                    <span class="ml-1 opacity-60">{{ $counts[$status] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Members list --}}
    @if($members->isEmpty())
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-heart text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400">No members yet.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach($members as $member)
                <div class="flex items-center justify-between p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center overflow-hidden">
                            @if($member->user->avatar_path)
                                <img src="{{ Storage::url($member->user->avatar_path) }}" class="w-full h-full object-cover" />
                            @else
                                <span class="text-xs font-medium text-zinc-500">
                                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $member->user->name }}
                            </p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <flux:badge size="sm"
                                    :color="$member->status === 'active' ? 'green' : ($member->status === 'pending' ? 'yellow' : 'red')">
                                    {{ ucfirst($member->status) }}
                                </flux:badge>
                                @if($member->type === 'subscribed')
                                    <flux:badge size="sm" color="blue">
                                        Subscriber · {{ $member->token_price }} tokens/mo
                                    </flux:badge>
                                @endif
                                @if($member->subscription_ends_at)
                                    <span class="text-xs text-zinc-400">
                                        Until {{ $member->subscription_ends_at->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($member->status === 'pending')
                            <flux:button
                                wire:click="approve({{ $member->id }})"
                                size="sm"
                                variant="primary"
                            >
                                Approve
                            </flux:button>
                        @endif

                        @if($member->status === 'blocked')
                            <flux:button
                                wire:click="unblock({{ $member->id }})"
                                size="sm"
                                variant="outline"
                            >
                                Unblock
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="block({{ $member->id }})"
                                wire:confirm="Block this member?"
                                size="sm"
                                variant="ghost"
                                icon="ban"
                            />
                        @endif

                        <flux:button
                            wire:click="remove({{ $member->id }})"
                            wire:confirm="Remove this member from your community?"
                            size="sm"
                            variant="ghost"
                            icon="trash"
                        />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $members->links() }}</div>
    @endif

</div>