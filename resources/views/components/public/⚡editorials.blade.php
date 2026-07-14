<?php

use App\Models\Editorial;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $format = '';

    public function with(): array
    {
        return [
            'editorials' => Editorial::published()
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->format, fn($q) => $q->where('primary_format', $this->format))
                ->latest('published_at')
                ->paginate(12),
        ];
    }

}; ?>

<div class="w-fit mx-auto px-1 sm:px-1 lg:px-2 py-12">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-2">
            Editorials
        </h1>
        <p class="text-zinc-500 dark:text-zinc-400">
            Ideas, essays and commentary in text, video, audio and PDF.
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 mb-8">
        <flux:input
            wire:model.live="search"
            placeholder="Search editorials..."
            icon="magnifying-glass"
            class="flex-1"
        />
        <flux:select wire:model.live="format" class="w-36">
            <flux:select.option value="">All formats</flux:select.option>
            <flux:select.option value="text">Text</flux:select.option>
            <flux:select.option value="video">Video</flux:select.option>
            <flux:select.option value="audio">Audio</flux:select.option>
            <flux:select.option value="pdf">PDF</flux:select.option>
        </flux:select>
    </div>

    {{-- Grid --}}
    @if($editorials->isEmpty())
        <div class="text-center py-16">
            <i class="ti ti-file-text text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-zinc-400">No editorials found.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($editorials as $editorial)
                <div class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">

                    {{-- Cover --}}
                    <div class="w-fit aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        @if($editorial->cover_image)
                            <img
                                src="{{ Storage::url($editorial->cover_image) }}"
                                alt="{{ $editorial->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="ti ti-file-text text-2xl text-zinc-300" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        {{-- Format & access --}}
                        <div class="flex items-center gap-2 mb-2">
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
                        </div>

                        {{-- Title --}}
                        <h2 class="text-sm font-medium text-zinc-900 dark:text-white leading-snug mb-1 line-clamp-2">
                            {{ $editorial->title }}
                        </h2>

                        {{-- Excerpt --}}
                        @if($editorial->excerpt)
                            <p class="text-xs text-zinc-400 line-clamp-2 mb-3">
                                {{ $editorial->excerpt }}
                            </p>
                        @endif

                        {{-- Date --}}
                        <p class="text-xs text-zinc-300 dark:text-zinc-600 mb-3">
                            {{ $editorial->published_at?->format('M d, Y') }}
                        </p>

                        {{-- CTA --}}
                        @auth
                            <a href="{{ route('editorial', $editorial->slug) }}"
                               class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Read editorial →
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Sign in to read →
                            </a>
                        @endauth
                    </div>
                </div>







                
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $editorials->links() }}
        </div>
    @endif

</div>