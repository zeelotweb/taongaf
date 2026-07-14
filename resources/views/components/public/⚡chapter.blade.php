<?php

use App\Models\Book;
use App\Models\Chapter;
use Livewire\Component;
use App\Services\PurchaseService;

new class extends Component {

    public Book $book;
    public Chapter $chapter;
    public string $purchaseMessage = '';

    public function with(): array
    {
        $isOwner = auth()->check() && auth()->id() === $this->book->user_id;

        $isAccessible = $isOwner
            || $this->chapter->isFree()
            || (auth()->check() && auth()->user()->hasPurchased($this->chapter))
            || (auth()->check() && auth()->user()->hasPurchased($this->book));

        return [
            'media'        => $this->chapter->media()->ordered()->get(),
            'isOwner'      => $isOwner,
            'isAccessible' => $isAccessible,
            'prev'         => $this->book->chapters()
                ->published()
                ->where('sort_order', '<', $this->chapter->sort_order)
                ->orderBy('sort_order', 'desc')
                ->first(),
            'next'         => $this->book->chapters()
                ->published()
                ->where('sort_order', '>', $this->chapter->sort_order)
                ->orderBy('sort_order')
                ->first(),
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Back --}}
    <a href="{{ route('book', $book->slug) }}"
       class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1 mb-8">
        ← Back to {{ $book->title }}
    </a>

    {{-- Header --}}
    <div class="mb-8">
        <p class="text-xs text-zinc-400 uppercase tracking-wide mb-2">
            Chapter {{ $chapter->sort_order + 1 }}
        </p>
        <h1 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-3">
            {{ $chapter->title }}
        </h1>
        @if($chapter->excerpt)
            <p class="text-lg text-zinc-500 dark:text-zinc-400 leading-relaxed">
                {{ $chapter->excerpt }}
            </p>
        @endif
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-800 mb-8"></div>

    {{-- Content --}}
    @guest
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-lock text-3xl text-zinc-300 mb-3 block" aria-hidden="true"></i>
            <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                Sign in to read this chapter.
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
                    <p class="text-xs text-zinc-500">You created this chapter</p>
                </div>
            @endif

            {{-- Text content --}}
            @if($chapter->primary_format === 'text' && $chapter->body)
                <div class="prose dark:prose-invert max-w-none mb-8">
                    {!! nl2br(e($chapter->body)) !!}
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
            <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl mb-8">
                <i class="ti ti-lock text-3xl text-zinc-300 mb-3 block" aria-hidden="true"></i>

                @if($purchaseMessage)
                    <p class="text-sm mb-4 {{ str_contains($purchaseMessage, 'successful') ? 'text-green-600' : 'text-red-500' }}">
                        {{ $purchaseMessage }}
                    </p>
                @else
                    <p class="text-zinc-600 dark:text-zinc-400 mb-2">
                        This chapter requires
                        {{ $chapter->token_price ?? $book->token_price }} tokens to access.
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
                            Purchase access
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

    <livewire:interactions.engage :model="$chapter" />
    
    {{-- Navigation --}}
    <div class="flex items-center justify-between pt-8 border-t border-zinc-100 dark:border-zinc-800">
        @if($prev)
            <a href="{{ route('chapter', [$book->slug, $prev->slug]) }}"
               class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                ← {{ $prev->title }}
            </a>
        @else
            <span></span>
        @endif

        @if($next)
            <a href="{{ route('chapter', [$book->slug, $next->slug]) }}"
               class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                {{ $next->title }} →
            </a>
        @endif
    </div>

</div>