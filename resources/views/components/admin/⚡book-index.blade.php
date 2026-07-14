<?php

use App\Models\Book;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $genre = '';
    public string $status = '';

    public function deleteBook(int $id): void
    {
        $this->authorize('delete-content');
        $book = Book::findOrFail($id);
        $book->delete();
        session()->flash('message', 'Book deleted.');
    }

    public function with(): array
    {
        return [
            'books' => Book::query()
                ->where('user_id', auth()->id())
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->genre, fn($q) => $q->where('genre', $this->genre))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->withCount('chapters')
                ->latest()
                ->paginate(12),
        ];
    }

}; ?>

<div>
    <div class="max-w-5xl mx-auto py-8 px-4">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Books</flux:heading>
            <flux:button href="{{ route('admin.books.create') }}" variant="primary" icon="plus">
                New book
            </flux:button>
        </div>

        @if(session()->has('message'))
            <flux:callout variant="success" icon="check-circle" class="mb-4">
                {{ session('message') }}
            </flux:callout>
        @endif

        {{-- Filters --}}
        <div class="flex gap-3 mb-6 flex-wrap">
            <flux:input
                wire:model.live="search"
                placeholder="Search books..."
                icon="magnifying-glass"
                class="flex-1 min-w-48"
            />
            <flux:select wire:model.live="genre" class="w-40">
                <flux:select.option value="">All genres</flux:select.option>
                <flux:select.option value="fiction">Fiction</flux:select.option>
                <flux:select.option value="non_fiction">Non fiction</flux:select.option>
                <flux:select.option value="biography">Biography</flux:select.option>
                <flux:select.option value="self_help">Self help</flux:select.option>
                <flux:select.option value="poetry">Poetry</flux:select.option>
                <flux:select.option value="essay_collection">Essay collection</flux:select.option>
                <flux:select.option value="other">Other</flux:select.option>
            </flux:select>
            <flux:select wire:model.live="status" class="w-36">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="draft">Draft</flux:select.option>
                <flux:select.option value="published">Published</flux:select.option>
                <flux:select.option value="archived">Archived</flux:select.option>
            </flux:select>
        </div>

        {{-- Cards --}}
        @forelse($books as $book)
            <div class="bg-white border border-zinc-200 rounded-xl overflow-hidden mb-4">

                {{-- Title --}}
                <div class="px-4 pt-4 pb-2">
                    <p class="font-medium text-zinc-900 text-base">{{ $book->title }}</p>
                    <p class="text-xs text-zinc-400 mt-0.5">{{ ucfirst(str_replace('_', ' ', $book->genre)) }}</p>
                </div>

                {{-- Cover image --}}
                <div class="px-4 pb-3">
                    @if($book->cover_image)
                        <img
                            src="{{ Storage::url($book->cover_image) }}"
                            alt="{{ $book->title }}"
                            class="w-full h-40 object-cover rounded-lg"
                        />
                    @else
                        <div class="w-full h-40 bg-zinc-100 rounded-lg flex items-center justify-center">
                            <i class="ti ti-book text-zinc-300 text-4xl" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="px-4 pb-3 flex flex-wrap gap-2">
                    <flux:badge size="sm" :color="$book->status === 'published' ? 'green' : ($book->status === 'archived' ? 'zinc' : 'yellow')">
                        {{ ucfirst($book->status) }}
                    </flux:badge>
                    <flux:badge size="sm" :color="$book->visibility === 'free' ? 'green' : 'blue'">
                        {{ $book->visibility === 'free' ? 'Free' : $book->token_price . ' tokens' }}
                    </flux:badge>
                    <flux:badge size="sm" color="zinc">
                        {{ $book->chapters_count }} {{ Str::plural('chapter', $book->chapters_count) }}
                    </flux:badge>
                    <span class="text-xs text-zinc-400 self-center ml-auto">
                        {{ $book->created_at->format('M d, Y') }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="px-4 py-3 border-t border-zinc-100 flex gap-2">
                    <flux:button
                        href="{{ route('admin.books.edit', $book) }}"
                        size="sm"
                        variant="outline"
                        icon="pencil"
                        class="flex-1"
                    >
                        Edit
                    </flux:button>
                    @can('delete-content')
                    <flux:button
                        wire:click="deleteBook({{ $book->id }})"
                        wire:confirm="Delete this book and all its chapters?"
                        size="sm"
                        variant="ghost"
                        icon="trash"
                    >
                        Delete
                    </flux:button>
                    @endcan
                </div>

            </div>
        @empty
            <div class="text-center py-16 text-zinc-400">
                <i class="ti ti-book text-4xl mb-3 block" aria-hidden="true"></i>
                <p class="text-sm">No books yet.</p>
                <a href="{{ route('admin.books.create') }}" class="text-zinc-600 underline text-sm mt-1 inline-block">
                    Create your first one.
                </a>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $books->links() }}
        </div>

    </div>
</div>