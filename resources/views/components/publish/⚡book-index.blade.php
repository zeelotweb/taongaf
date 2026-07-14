<?php

use App\Models\Book;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function deleteBook(int $id): void
    {
        $book = Book::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $book->delete();
        session()->flash('message', 'Book deleted.');
    }

    public function with(): array
    {
        return [
            'books' => Book::where('user_id', auth()->id())
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->withCount('chapters')
                ->latest()
                ->paginate(10),
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">My books</flux:heading>
        <flux:button href="{{ route('publish.books.create') }}" variant="primary" icon="plus">
            New book
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            {{ session('message') }}
        </flux:callout>
    @endif

    <div class="flex gap-3 mb-6">
        <flux:input wire:model.live="search" placeholder="Search..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="status" class="w-36">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="published">Published</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($books as $book)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                <div class="h-32 bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                    @if($book->cover_image)
                        <img src="{{ Storage::url($book->cover_image) }}" class="w-full h-full object-cover" />
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="ti ti-book text-3xl text-zinc-300" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <h3 class="font-medium text-zinc-900 dark:text-white text-sm mb-2 line-clamp-1">
                        {{ $book->title }}
                    </h3>
                    <div class="flex gap-2 mb-3">
                        <flux:badge size="sm" :color="$book->status === 'published' ? 'green' : 'yellow'">
                            {{ ucfirst($book->status) }}
                        </flux:badge>
                        <span class="text-xs text-zinc-400">
                            {{ $book->chapters_count }} {{ Str::plural('chapter', $book->chapters_count) }}
                        </span>
                    </div>
                    <div class="flex gap-2 border-t border-zinc-100 dark:border-zinc-800 pt-3">
                        <flux:button
                            href="{{ route('publish.books.edit', $book) }}"
                            size="sm" variant="ghost" icon="pencil" class="flex-1">
                            Edit
                        </flux:button>
                        <flux:button
                            wire:click="deleteBook({{ $book->id }})"
                            wire:confirm="Delete this book and all chapters?"
                            size="sm" variant="ghost" icon="trash" class="flex-1">
                            Delete
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-3 text-center py-12 text-zinc-400">
                No books yet.
                <a href="{{ route('publish.books.create') }}" class="text-zinc-600 underline ml-1">
                    Create your first one.
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $books->links() }}</div>

</div>