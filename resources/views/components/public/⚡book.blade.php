<?php

use App\Models\Book;
use Livewire\Component;

new class extends Component {

    public Book $book;

    public function with(): array
    {
        return [
            'chapters' => $this->book->chapters()
                ->published()
                ->orderBy('sort_order')
                ->get(),
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Back --}}
    <a href="{{ route('books') }}"
       class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1 mb-8">
        ← Back to books
    </a>

    <div class="grid md:grid-cols-3 gap-8 mb-12">

        {{-- Cover --}}
        <div class="aspect-[3/4] bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
            @if($book->cover_image)
                <img
                    src="{{ Storage::url($book->cover_image) }}"
                    alt="{{ $book->title }}"
                    class="w-full h-full object-cover"
                />
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="ti ti-book text-4xl text-zinc-300" aria-hidden="true"></i>
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="md:col-span-2">
            <p class="text-xs text-zinc-400 uppercase tracking-wide mb-2">
                {{ ucfirst(str_replace('_', ' ', $book->genre)) }}
            </p>

            <h1 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-4">
                {{ $book->title }}
            </h1>

            @if($book->synopsis)
                <p class="text-zinc-500 dark:text-zinc-400 leading-relaxed mb-6">
                    {{ $book->synopsis }}
                </p>
            @endif

            <div class="flex items-center gap-3 mb-6">
                @if($book->visibility === 'tokens')
                    <span class="text-sm px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                        {{ $book->token_price }} tokens
                    </span>
                @else
                    <span class="text-sm px-3 py-1 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full">
                        Free
                    </span>
                @endif
                <span class="text-sm text-zinc-400">
                    {{ $chapters->count() }} {{ Str::plural('chapter', $chapters->count()) }}
                </span>
            </div>

            @auth
                @if($book->visibility === 'tokens' && !auth()->user()->hasPurchased($book))
                    <flux:button variant="primary">
                        Purchase book for {{ $book->token_price }} tokens
                    </flux:button>
                @endif
            @else
                <div class="flex gap-3">
                    <a href="{{ route('login') }}">
                        <flux:button variant="primary">Sign in to read</flux:button>
                    </a>
                </div>
            @endauth
        </div>
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-800 mb-8"></div>

    {{-- Chapters --}}
    <div>
        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-6">
            Chapters
        </p>

        @if($chapters->isEmpty())
            <p class="text-sm text-zinc-400">No chapters published yet.</p>
        @else
            <div class="space-y-2">
                @foreach($chapters as $chapter)
                    @php
                        $isAccessible = $chapter->isFree()
                            || (auth()->check() && auth()->user()->hasPurchased($chapter))
                            || (auth()->check() && auth()->user()->hasPurchased($book));
                    @endphp

                    <div class="flex items-center justify-between p-4 border border-zinc-100 dark:border-zinc-800 rounded-lg hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-zinc-300 dark:text-zinc-600 w-6">
                                {{ $chapter->sort_order + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $chapter->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-zinc-400 uppercase tracking-wide">
                                        {{ ucfirst($chapter->primary_format) }}
                                    </span>
                                    @if($chapter->is_free_preview)
                                        <span class="text-xs text-green-500">Free preview</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($isAccessible)
                            <a href="{{ route('chapter', [$book->slug, $chapter->slug]) }}"
                               class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Read →
                            </a>
                        @else
                            <i class="ti ti-lock text-zinc-300" aria-hidden="true"></i>
                        @endif
                    </div>




<livewire:interactions.engage :model="$book" />

                @endforeach
            </div>
        @endif
    </div>

</div>