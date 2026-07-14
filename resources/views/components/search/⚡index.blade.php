<?php

use App\Models\Editorial;
use App\Models\Book;
use App\Models\Chapter;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $query = '';
    public string $type = 'all';
    public string $format = '';
    public string $visibility = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
        $this->format = '';
    }

    public function with(): array
    {
        if (strlen($this->query) < 2) {
            return [
                'editorials' => collect(),
                'books'      => collect(),
                'chapters'   => collect(),
                'total'      => 0,
            ];
        }

        $editorials = collect();
        $books      = collect();
        $chapters   = collect();

        if (in_array($this->type, ['all', 'editorials'])) {
            $editorials = Editorial::published()
                ->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->query . '%')
                      ->orWhere('excerpt', 'like', '%' . $this->query . '%')
                      ->orWhere('body', 'like', '%' . $this->query . '%');
                })
                ->when($this->format, fn($q) => $q->where('primary_format', $this->format))
                ->when($this->visibility, fn($q) => $q->where('visibility', $this->visibility))
                ->latest('published_at')
                ->get();
        }

        if (in_array($this->type, ['all', 'books'])) {
            $books = Book::published()
                ->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->query . '%')
                      ->orWhere('synopsis', 'like', '%' . $this->query . '%');
                })
                ->when($this->visibility, fn($q) => $q->where('visibility', $this->visibility))
                ->latest('published_at')
                ->get();
        }

        if (in_array($this->type, ['all', 'chapters'])) {
            $chapters = Chapter::published()
                ->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->query . '%')
                      ->orWhere('excerpt', 'like', '%' . $this->query . '%')
                      ->orWhere('body', 'like', '%' . $this->query . '%');
                })
                ->when($this->format, fn($q) => $q->where('primary_format', $this->format))
                ->latest('published_at')
                ->get();
        }

        $total = $editorials->count() + $books->count() + $chapters->count();

        return compact('editorials', 'books', 'chapters', 'total');
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="mb-8">
        <flux:heading size="xl" class="mb-4">Search</flux:heading>

        <flux:input
            wire:model.live.debounce.300ms="query"
            placeholder="Search editorials, books, chapters..."
            icon="magnifying-glass"
            size="lg"
            autofocus
        />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-8">
        {{-- Type --}}
        <div class="flex gap-2">
            @foreach(['all', 'editorials', 'books', 'chapters'] as $option)
                <button
                    wire:click="$set('type', '{{ $option }}')"
                    class="text-xs px-3 py-1.5 rounded-full transition-colors
                        {{ $type === $option
                            ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                            : 'border border-zinc-200 dark:border-zinc-700 text-zinc-400 hover:text-zinc-600' }}"
                >
                    {{ ucfirst($option) }}
                </button>
            @endforeach
        </div>

        {{-- Format -- only for editorials/chapters --}}
        @if(in_array($type, ['all', 'editorials', 'chapters']))
            <flux:select wire:model.live="format" class="w-36 text-xs">
                <flux:select.option value="">All formats</flux:select.option>
                <flux:select.option value="text">Text</flux:select.option>
                <flux:select.option value="video">Video</flux:select.option>
                <flux:select.option value="audio">Audio</flux:select.option>
                <flux:select.option value="pdf">PDF</flux:select.option>
            </flux:select>
        @endif

        {{-- Visibility --}}
        <flux:select wire:model.live="visibility" class="w-36 text-xs">
            <flux:select.option value="">All access</flux:select.option>
            <flux:select.option value="free">Free only</flux:select.option>
            <flux:select.option value="tokens">Premium only</flux:select.option>
        </flux:select>
    </div>

    {{-- Results --}}
    @if(strlen($query) < 2)
        <div class="text-center py-16">
            <i class="ti ti-search text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400">Type at least 2 characters to search.</p>
        </div>

    @elseif($total === 0)
        <div class="text-center py-16">
            <i class="ti ti-mood-empty text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400">No results for "{{ $query }}"</p>
        </div>

    @else
        <p class="text-xs text-zinc-400 mb-6">
            {{ $total }} {{ Str::plural('result', $total) }} for "{{ $query }}"
        </p>

        {{-- Editorials --}}
        @if($editorials->isNotEmpty())
            <div class="mb-8">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Editorials ({{ $editorials->count() }})
                </p>
                <div class="space-y-2">
                    @foreach($editorials as $editorial)
                        <a href="{{ route('editorial', $editorial->slug) }}"
                           class="flex items-center gap-4 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">
                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
                                @if($editorial->cover_image)
                                    <img src="{{ Storage::url($editorial->cover_image) }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-file-text text-zinc-300" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $editorial->title }}
                                </p>
                                @if($editorial->excerpt)
                                    <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $editorial->excerpt }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <flux:badge size="sm" color="zinc">{{ ucfirst($editorial->primary_format) }}</flux:badge>
                                @if($editorial->visibility === 'tokens')
                                    <flux:badge size="sm" color="blue">{{ $editorial->token_price }} tokens</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">Free</flux:badge>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Books --}}
        @if($books->isNotEmpty())
            <div class="mb-8">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Books ({{ $books->count() }})
                </p>
                <div class="space-y-2">
                    @foreach($books as $book)
                        <a href="{{ route('book', $book->slug) }}"
                           class="flex items-center gap-4 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">
                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
                                @if($book->cover_image)
                                    <img src="{{ Storage::url($book->cover_image) }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-book text-zinc-300" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $book->title }}
                                </p>
                                @if($book->synopsis)
                                    <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $book->synopsis }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <flux:badge size="sm" color="zinc">{{ ucfirst(str_replace('_', ' ', $book->genre)) }}</flux:badge>
                                @if($book->visibility === 'tokens')
                                    <flux:badge size="sm" color="blue">{{ $book->token_price }} tokens</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">Free</flux:badge>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Chapters --}}
        @if($chapters->isNotEmpty())
            <div class="mb-8">
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Chapters ({{ $chapters->count() }})
                </p>
                <div class="space-y-2">
                    @foreach($chapters as $chapter)
                        <a href="{{ route('chapter', [$chapter->book->slug, $chapter->slug]) }}"
                           class="flex items-center gap-4 p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">
                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
                                @if($chapter->cover_image)
                                    <img src="{{ Storage::url($chapter->cover_image) }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-file-text text-zinc-300" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-zinc-400 mb-0.5">{{ $chapter->book->title }}</p>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $chapter->title }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <flux:badge size="sm" color="zinc">{{ ucfirst($chapter->primary_format) }}</flux:badge>
                                @if($chapter->is_free_preview)
                                    <flux:badge size="sm" color="green">Free preview</flux:badge>
                                @elseif($chapter->visibility === 'tokens')
                                    <flux:badge size="sm" color="blue">{{ $chapter->token_price }} tokens</flux:badge>
                                @else
                                    <flux:badge size="sm" color="green">Free</flux:badge>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

</div>