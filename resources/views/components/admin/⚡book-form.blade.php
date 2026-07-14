<?php

use App\Models\Book;
use App\Models\Media;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public ?Book $book = null;

    public string $title = '';
    public string $slug = '';
    public string $synopsis = '';
    public string $genre = 'other';
    public string $status = 'draft';
    public string $visibility = 'free';
    public ?int $token_price = null;
    public string $existing_cover = '';
    public bool $isEditing = false;
    public bool $mediaProcessing = false;
    public bool $isAdmin = false;

    protected function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'slug'        => 'required|string|unique:books,slug,' . ($this->book?->id ?? 'NULL'),
            'synopsis'    => 'nullable|string|max:1000',
            'genre'       => 'required|in:fiction,non_fiction,biography,self_help,poetry,essay_collection,other',
            'status'      => 'required|in:draft,published,archived',
            'visibility'  => 'required|in:free,tokens',
            'token_price' => 'nullable|integer|min:1',
        ];
    }

    public function mount(Book $book): void
    {
        $this->isAdmin = request()->routeIs('admin.*');

        if ($book->exists) {
            $this->isEditing = true;
            $this->book      = $book;
            $this->fill($book->only([
                'title', 'slug', 'synopsis', 'genre',
                'status', 'visibility', 'token_price'
            ]));
            $this->existing_cover = $book->cover_image ?? '';
        }
    }

    public function updatedTitle(string $value): void
    {
        if (!$this->isEditing) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedVisibility(string $value): void
    {
        if ($value === 'free') {
            $this->token_price = null;
        }
    }

    public function notifyUploadComplete(string $path, string $type): void
    {
        $this->mediaProcessing = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id'      => auth()->id(),
            'title'        => $this->title,
            'slug'         => $this->slug,
            'synopsis'     => $this->synopsis,
            'genre'        => $this->genre,
            'status'       => $this->status,
            'visibility'   => $this->visibility,
            'token_price'  => $this->visibility === 'tokens' ? $this->token_price : null,
            'published_at' => $this->status === 'published' ? now() : null,
        ];

        if ($this->isEditing) {
            $this->book->update($data);
            session()->flash('message', 'Book updated successfully.');
        } else {
            $this->book = Book::create($data);
            session()->flash('message', 'Book created successfully.');
        }

        $route = $this->isAdmin
            ? route('admin.books.edit', $this->book)
            : route('publish.books.edit', $this->book);

        $this->redirect($route, navigate: true);
    }

}; ?>

<div>
    <div class="max-w-3xl mx-auto py-8 px-4 space-y-8">

        {{-- Page heading --}}
        <div class="flex items-center justify-between">
            <flux:heading size="xl">
                {{ $isEditing ? 'Edit book' : 'New book' }}
            </flux:heading>
            <flux:badge :color="$status === 'published' ? 'green' : ($status === 'archived' ? 'zinc' : 'yellow')">
                {{ ucfirst($status) }}
            </flux:badge>
        </div>

        {{-- Flash message --}}
        @if(session()->has('message'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('message') }}
            </flux:callout>
        @endif

        {{-- Step 1 — Basic info --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">01 — Basic info</p>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model.live="title" placeholder="Book title..." />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="slug" />
                <flux:error name="slug" />
            </flux:field>

            <flux:field>
                <flux:label>Genre</flux:label>
                <flux:select wire:model="genre">
                    <flux:select.option value="fiction">Fiction</flux:select.option>
                    <flux:select.option value="non_fiction">Non fiction</flux:select.option>
                    <flux:select.option value="biography">Biography</flux:select.option>
                    <flux:select.option value="self_help">Self help</flux:select.option>
                    <flux:select.option value="poetry">Poetry</flux:select.option>
                    <flux:select.option value="essay_collection">Essay collection</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                <flux:error name="genre" />
            </flux:field>

            <flux:field>
                <flux:label>Synopsis</flux:label>
                <flux:textarea wire:model="synopsis" rows="4" placeholder="Brief synopsis..." />
                <flux:error name="synopsis" />
            </flux:field>
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

        {{-- Step 2 — Chapters --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">02 — Chapters</p>

            @if(!$isEditing)
                <div class="p-4 bg-amber-50 border border-amber-100 rounded-lg">
                    <p class="text-sm text-amber-600">Save the book first — then add chapters.</p>
                </div>
            @else
                @forelse($book->chapters()->orderBy('sort_order')->get() as $chapter)
                    <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-zinc-400">{{ $chapter->sort_order + 1 }}</span>
                            <div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $chapter->title }}
                                </p>
                                <p class="text-xs text-zinc-400">
                                    {{ ucfirst($chapter->primary_format) }}
                                    @if($chapter->is_free_preview)
                                        · <span class="text-green-500">Free preview</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <flux:button
                            href="{{ $isAdmin ? route('admin.chapters.edit', [$book, $chapter]) : route('publish.chapters.edit', [$book, $chapter]) }}"
                            size="sm"
                            variant="ghost"
                            icon="pencil"
                        />
                    </div>
                @empty
                    <p class="text-sm text-zinc-400">No chapters yet.</p>
                @endforelse

                <flux:button
                    href="{{ $isAdmin ? route('admin.chapters.create', $book) : route('publish.chapters.create', $book) }}"
                    variant="outline"
                    icon="plus"
                    class="w-full"
                >
                    Add chapter
                </flux:button>
            @endif
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

        {{-- Step 3 — Cover & meta --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">03 — Cover & meta</p>

            <flux:field>
                <flux:label>Cover image</flux:label>
                @if($existing_cover)
                    <img src="{{ Storage::url($existing_cover) }}" class="h-32 rounded-lg object-cover mb-2" />
                @endif
                <div wire:ignore>
                    <input
                        type="file"
                        class="md:max-w-1/4"
                        x-init="
                            initCoverPond($el, {
                                wire: $wire,
                                modelId: {{ $book->id ?? 'null' }},
                                modelType: 'book',
                            })
                        "
                    />
                </div>
            </flux:field>

            <flux:field>
                <flux:label>Access</flux:label>
                <flux:select wire:model.live="visibility">
                    <flux:select.option value="free">Free</flux:select.option>
                    <flux:select.option value="tokens">Tokens</flux:select.option>
                </flux:select>
                <flux:error name="visibility" />
            </flux:field>

            @if($visibility === 'tokens')
                <flux:field>
                    <flux:label>Token price</flux:label>
                    <flux:input type="number" wire:model="token_price" min="1" placeholder="e.g. 10" />
                    <flux:error name="token_price" />
                </flux:field>
            @endif
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

        {{-- Step 4 — Publish --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">04 — Publish</p>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model.live="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="published">Published</flux:select.option>
                    <flux:select.option value="archived">Archived</flux:select.option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
            <flux:button wire:click="save" wire:loading.attr="disabled" variant="primary">
                <span wire:loading.remove>{{ $isEditing ? 'Update book' : 'Save book' }}</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>

    </div>
</div>