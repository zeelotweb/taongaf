<x-layouts::admin :title="__('Dashboard')">

    <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-8">

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider">Editorials</p>
                <p class="text-2xl font-medium mt-1">{{ $stats['editorials_count'] }}</p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider">Books</p>
                <p class="text-2xl font-medium mt-1">{{ $stats['books_count'] }}</p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider">Published</p>
                <p class="text-2xl font-medium mt-1">{{ $stats['published_count'] }}</p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider">Drafts</p>
                <p class="text-2xl font-medium mt-1">{{ $stats['draft_count'] }}</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap gap-3">

        	<flux:modal.trigger name="create-editorial">
			    <flux:button icon="plus" variant="primary">
			        New editorial
			    </flux:button>
			</flux:modal.trigger>

			<flux:modal.trigger name="create-book">
			    <flux:button icon="plus">
			        New book
			    </flux:button>
			</flux:modal.trigger>

			<flux:modal.trigger name="view-editorials">
			    <flux:button icon="document-text">
			        View editorials
			    </flux:button>
			</flux:modal.trigger>

			<flux:modal.trigger name="view-books">
			    <flux:button icon="book-open">
			        View books
			    </flux:button>
			</flux:modal.trigger>
        </div>

        {{-- Recent content --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <flux:heading size="lg" class="mb-3">Recent editorials</flux:heading>
                <div class="space-y-2">
                    @forelse($recentEditorials as $editorial)
                        <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg flex items-center justify-between">
                            <span class="text-sm">{{ $editorial->title }}</span>
                            <flux:badge size="sm" :color="$editorial->status === 'published' ? 'green' : 'yellow'">
                                {{ ucfirst($editorial->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">No editorials yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <flux:heading size="lg" class="mb-3">Recent books</flux:heading>
                <div class="space-y-2">
                    @forelse($recentBooks as $book)
                        <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg flex items-center justify-between">
                            <span class="text-sm">{{ $book->title }}</span>
                            <flux:badge size="sm" :color="$book->status === 'published' ? 'green' : 'yellow'">
                                {{ ucfirst($book->status) }}
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">No books yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    {{-- Modals --}}
    <flux:modal name="create-editorial" class="w-full max-w-xl py-12 px-2"
                    flyout>
        <livewire:admin.editorial-form />
    </flux:modal>

    <flux:modal name="create-book" class="w-full max-w-xl py-12 px-2"
                    flyout>
        <livewire:admin.book-form />
    </flux:modal>

    <flux:modal name="view-editorials" class="w-full max-w-xl py-12 px-2"
                    flyout>
        <livewire:admin.editorial-index />
    </flux:modal>

    <flux:modal name="view-books" class="w-full max-w-xl py-12 px-2"
                    flyout>
        <livewire:admin.book-index />
    </flux:modal>

</x-layouts::admin>