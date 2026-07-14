<?php

use App\Models\Editorial;
use Livewire\Component;
use App\Services\PurchaseService;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public Editorial $editorial;
    public string $purchaseMessage = '';
    public bool $purchasing = false;

    public function with(): array
    {
        $isOwner = auth()->check() && auth()->id() === $this->editorial->user_id;

        return [
            'media'        => $this->editorial->media()->ordered()->get(),
            'isOwner'      => $isOwner,
            'isAccessible' => $isOwner
                || $this->editorial->isFree()
                || (auth()->check() && auth()->user()->hasPurchased($this->editorial)),
            'related'      => Editorial::published()
                ->where('id', '!=', $this->editorial->id)
                ->latest('published_at')
                ->take(3)
                ->get(),
        ];
    }

public function purchase(): void
{
    if (!auth()->check()) {
        $this->redirect(route('login'));
        return;
    }

    $this->purchasing = true;

    $service = app(\App\Services\PurchaseService::class);
    $result  = $service->purchase(
        buyer:          auth()->user(),
        content:        $this->editorial,
        publisher:      $this->editorial->user,
        referralToken:  session('referral_token'),
        profileOwnerId: session('profile_owner_id'),
    );

    $this->purchasing      = false;
    $this->purchaseMessage = $result['message'];

    if ($result['success']) {
        session()->forget(['referral_token', 'profile_owner_id']);
        $this->dispatch('purchased');
    }

    if (isset($result['redirect'])) {
        $this->redirect($result['redirect']);
    }
}

}; ?>

<div class="max-fit-auto px-1 sm:px-1 lg:px-2 py-8">

    {{-- Back --}}
    <a href="{{ route('editorials') }}"
       class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1 mb-8">
        ← Back to editorials
    </a>

    {{-- Cover --}}
    @if($editorial->cover_image)
        <div class="w-fit aspect-video rounded-xl overflow-hidden mb-8">
            <img
                src="{{ Storage::url($editorial->cover_image) }}"
                alt="{{ $editorial->title }}"
                class="w-full h-full object-cover"
            />
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs text-zinc-400 uppercase tracking-wide">
                {{ ucfirst($editorial->primary_format) }}
            </span>
            @if($editorial->visibility === 'tokens')
                <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                    {{ $editorial->token_price }} tokens
                </span>
            @else
                <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full">
                    Free
                </span>
            @endif
            <span class="text-xs text-zinc-300 dark:text-zinc-600">
                {{ $editorial->published_at?->format('M d, Y') }}
            </span>
        </div>

        <h1 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-3">
            {{ $editorial->title }}
        </h1>

        @if($editorial->excerpt)
            <p class="text-lg text-zinc-500 dark:text-zinc-400 leading-relaxed">
                {{ $editorial->excerpt }}
            </p>
        @endif
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-800 mb-8"></div>

    {{-- Content --}}
    @guest
        {{-- Guest gate --}}
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-lock text-3xl text-zinc-300 mb-3 block" aria-hidden="true"></i>
            <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                Sign in to read this editorial.
            </p>
            <div class="flex gap-3 justify-center">
                <a href="{{ route('login') }}">
                    <flux:button variant="primary">Sign in</flux:button>
                </a>
                <a href="{{ route('register') }}">
                    <flux:button>Create account</flux:button>
                </a>
            </div>
        </div>
    @endguest

    @auth
        @if($isAccessible)
            {{-- Owner badge --}}
            @if($isOwner)
                <div class="flex items-center gap-2 mb-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <i class="ti ti-crown text-zinc-400 text-sm" aria-hidden="true"></i>
                    <p class="text-xs text-zinc-500">You created this editorial</p>
                </div>
            @endif

            {{-- Text content --}}
            @if($editorial->primary_format === 'text' && $editorial->body)
                <div class="prose dark:prose-invert max-w-none">
                    {!! nl2br(e($editorial->body)) !!}
                </div>
            @endif

            {{-- Media --}}
            @foreach($media as $item)
                <div class="mb-6">
                    @if($item->isVideo())
                        <video
                            controls
                            class="w-full rounded-xl"
                            poster="{{ $item->thumbnail_url }}"
                        >
                            <source src="{{ Storage::url($item->path) }}" type="{{ $item->mime_type }}">
                        </video>
                        @if($item->duration)
                            <p class="text-xs text-zinc-400 mt-2">
                                Duration: {{ gmdate('H:i:s', $item->duration) }}
                            </p>
                        @endif
                    @elseif($item->isAudio())
                        <audio controls class="w-full">
                            <source src="{{ Storage::url($item->path) }}" type="{{ $item->mime_type }}">
                        </audio>
                        @if($item->duration)
                            <p class="text-xs text-zinc-400 mt-2">
                                Duration: {{ gmdate('H:i:s', $item->duration) }}
                            </p>
                        @endif
                    @elseif($item->isPdf())
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="ti ti-file-text text-zinc-400 text-xl" aria-hidden="true"></i>
                                <div>
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $item->original_name }}
                                    </p>
                                    <p class="text-xs text-zinc-400">{{ $item->formattedSize() }}</p>
                                </div>
                            </div>
                            <a href="{{ Storage::url($item->path) }}"
                               target="_blank"
                               class="text-xs text-blue-600 hover:text-blue-700">
                                Download PDF →
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach

        @else
            {{-- Token gate --}}
            <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
                <i class="ti ti-lock text-3xl text-zinc-300 mb-3 block" aria-hidden="true"></i>

                @if($purchaseMessage)
                    <p class="text-sm mb-4 {{ str_contains($purchaseMessage, 'successful') ? 'text-green-600' : 'text-red-500' }}">
                        {{ $purchaseMessage }}
                    </p>
                @else
                    <p class="text-zinc-600 dark:text-zinc-400 mb-2">
                        This editorial requires {{ $editorial->token_price }} tokens to access.
                    </p>
                    <p class="text-xs text-zinc-400 mb-4">
                        Your balance: {{ auth()->user()->wallet?->token_balance ?? 0 }} tokens
                    </p>
                @endif

                <div class="flex gap-3 justify-center">
                    <flux:button
                        wire:click="purchase"
                        wire:loading.attr="disabled"
                        variant="primary"
                    >
                        <span wire:loading.remove wire:target="purchase">
                            Purchase for {{ $editorial->token_price }} tokens
                        </span>
                        <span wire:loading wire:target="purchase">Processing...</span>
                    </flux:button>

                    <a href="{{ route('tokens.index') }}">
                        <flux:button variant="outline">Buy more tokens</flux:button>
                    </a>
                </div>
            </div>
        @endif
    @endauth



<livewire:interactions.engage :model="$editorial" />

    <div class="border-t border-zinc-100 dark:border-zinc-800 mt-12 pt-8"></div>

    {{-- Related --}}
    @if($related->isNotEmpty())
        <div>
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-6">
                More editorials
            </p>
            <div class="grid sm:grid-cols-3 gap-6">
                @foreach($related as $item)
                    <div class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 transition-colors">
                        <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            @if($item->cover_image)
                                <img
                                    src="{{ Storage::url($item->cover_image) }}"
                                    alt="{{ $item->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="ti ti-file-text text-zinc-300 text-xl" aria-hidden="true"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-xs font-medium text-zinc-900 dark:text-white line-clamp-2 mb-2">
                                {{ $item->title }}
                            </h3>
                            <a href="{{ route('editorial', $item->slug) }}"
                               class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors">
                                Read →
                            </a>
                        </div>
                    </div>

                        

                @endforeach
            </div>
        </div>
    @endif

</div>