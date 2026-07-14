<?php

use App\Models\Bookmark;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $filter = 'all';

    public function removeBookmark(int $id): void
    {
        Bookmark::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function with(): array
    {
        return [
            'bookmarks' => Bookmark::where('user_id', Auth::id())
                ->with('bookmarkable')
                ->when($this->filter !== 'all', function ($q) {
                    $type = match($this->filter) {
                        'editorials' => 'App\Models\Editorial',
                        'books'      => 'App\Models\Book',
                        'chapters'   => 'App\Models\Chapter',
                        default      => null,
                    };
                    if ($type) $q->where('bookmarkable_type', $type);
                })
                ->latest()
                ->paginate(12),
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <flux:heading size="xl">Bookmarks</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="flex gap-2 mb-8">
        @foreach(['all', 'editorials', 'books', 'chapters'] as $option)
            <button
                wire:click="$set('filter', '{{ $option }}')"
                class="text-xs px-3 py-1.5 rounded-full transition-colors
                    {{ $filter === $option
                        ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                        : 'border border-zinc-200 dark:border-zinc-700 text-zinc-400 hover:text-zinc-600' }}"
            >
                {{ ucfirst($option) }}
            </button>
        @endforeach
    </div>

    {{-- Bookmarks list --}}
    @if($bookmarks->isEmpty())
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-bookmark text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400">No bookmarks yet.</p>
            <p class="text-xs text-zinc-300 dark:text-zinc-600 mt-1">
                Save editorials, books and chapters to read later.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($bookmarks as $bookmark)
                @if($bookmark->bookmarkable)
                    @php $content = $bookmark->bookmarkable; @endphp
                    <div class="flex items-center justify-between p-4 border border-zinc-100 dark:border-zinc-800 rounded-xl hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">

                        <div class="flex items-center gap-4">
                            {{-- Cover --}}
                            <div class="w-14 h-14 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
                                @if($content->cover_image)
                                    <img
                                        src="{{ Storage::url($content->cover_image) }}"
                                        alt="{{ $content->title }}"
                                        class="w-full h-full object-cover"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-file-text text-zinc-300 text-lg" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>

                            <div>
                                {{-- Type badge --}}
                                <p class="text-xs text-zinc-400 mb-1">
                                    {{ class_basename($bookmark->bookmarkable_type) }}
                                </p>

                                {{-- Title --}}
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $content->title }}
                                </p>

                                {{-- Date --}}
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    Saved {{ $bookmark->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Read link --}}
                            @php
                                $route = match(class_basename($bookmark->bookmarkable_type)) {
                                    'Editorial' => route('editorial', $content->slug),
                                    'Book'      => route('book', $content->slug),
                                    default     => '#',
                                };
                            @endphp
                            <a href="{{ $route }}"
                               class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Read →
                            </a>

                            {{-- Remove --}}
                            <button
                                wire:click="removeBookmark({{ $bookmark->id }})"
                                wire:confirm="Remove this bookmark?"
                                class="text-xs text-zinc-300 dark:text-zinc-600 hover:text-red-400 transition-colors"
                            >
                                <i class="ti ti-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-6">
            {{ $bookmarks->links() }}
        </div>
    @endif

</div>