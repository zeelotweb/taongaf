<?php

use App\Models\Book;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $genre = '';

    public function with(): array
    {
        return [
            'books' => Book::published()
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->genre, fn($q) => $q->where('genre', $this->genre))
                ->withCount('chapters')
                ->latest('published_at')
                ->paginate(12),
        ];
    }

}; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-2">
            Books
        </h1>
        <p class="text-zinc-500 dark:text-zinc-400">
            Full length works from our writers and publishers.
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 mb-8">
        <flux:input
            wire:model.live="search"
            placeholder="Search books..."
            icon="magnifying-glass"
            class="flex-1"
        />
        <flux:select wire:model.live="genre" class="w-44">
            <flux:select.option value="">All genres</flux:select.option>
            <flux:select.option value="fiction">Fiction</flux:select.option>
            <flux:select.option value="non_fiction">Non fiction</flux:select.option>
            <flux:select.option value="biography">Biography</flux:select.option>
            <flux:select.option value="self_help">Self help</flux:select.option>
            <flux:select.option value="poetry">Poetry</flux:select.option>
            <flux:select.option value="essay_collection">Essay collection</flux:select.option>
            <flux:select.option value="other">Other</flux:select.option>
        </flux:select>
    </div>

    {{-- Grid --}}
    @if($books->isEmpty())
        <div class="text-center py-16">
            <i class="ti ti-book text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-zinc-400">No books found.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($books as $book)
                <a href="{{ route('book', $book->slug) }}"
                   class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">

                    {{-- Cover --}}
                    <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        @if($book->cover_image)
                            <img
                                src="{{ Storage::url($book->cover_image) }}"
                                alt="{{ $book->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="ti ti-book text-2xl text-zinc-300" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <p class="text-xs text-zinc-400 mb-1">
                            {{ ucfirst(str_replace('_', ' ', $book->genre)) }}
                        </p>

                        <h2 class="text-sm font-medium text-zinc-900 dark:text-white mb-1 line-clamp-2">
                            {{ $book->title }}
                        </h2>

                        @if($book->synopsis)
                            <p class="text-xs text-zinc-400 line-clamp-2 mb-3">
                                {{ $book->synopsis }}
                            </p>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-zinc-300 dark:text-zinc-600">
                                {{ $book->chapters_count }} {{ Str::plural('chapter', $book->chapters_count) }}
                            </span>
                            @if($book->visibility === 'tokens')
                                <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                                    {{ $book->token_price }} tokens
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full">
                                    Free
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $books->links() }}
        </div>
    @endif

</div>