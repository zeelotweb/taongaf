<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Media;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public ?Book $book = null;
    public ?Chapter $chapter = null;

    public string $title = '';
    public string $slug = '';
    public string $excerpt = '';
    public string $body = '';
    public string $primary_format = 'text';
    public string $status = 'draft';
    public string $visibility = 'free';
    public ?int $token_price = null;
    public bool $is_free_preview = false;
    public int $sort_order = 0;
    public string $existing_cover = '';
    public bool $isEditing = false;
    public bool $mediaProcessing = false;
    public bool $isAdmin = false;

    protected function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'slug'            => 'required|string|unique:chapters,slug,' . ($this->chapter?->id ?? 'NULL'),
            'excerpt'         => 'nullable|string|max:500',
            'body'            => 'nullable|string',
            'primary_format'  => 'required|in:text,video,audio,pdf',
            'status'          => 'required|in:draft,published,archived',
            'visibility'      => 'required|in:free,tokens',
            'token_price'     => 'nullable|integer|min:1',
            'is_free_preview' => 'boolean',
            'sort_order'      => 'integer|min:0',
        ];
    }

    public function mount(Book $book, Chapter $chapter): void
    {
        $this->book    = $book;
        $this->isAdmin = request()->routeIs('admin.*');

        if ($chapter->exists) {
            $this->isEditing = true;
            $this->chapter   = $chapter;
            $this->fill($chapter->only([
                'title', 'slug', 'excerpt', 'body',
                'primary_format', 'status', 'visibility',
                'token_price', 'is_free_preview', 'sort_order'
            ]));
            $this->existing_cover = $chapter->cover_image ?? '';
        } else {
            $this->sort_order = $book->chapters()->count();
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

    public function updatedIsFreePreview(bool $value): void
    {
        if ($value) {
            $this->visibility  = 'free';
            $this->token_price = null;
        }
    }

    public function notifyUploadComplete(string $path, string $type): void
    {
        $this->mediaProcessing = true;
    }

    public function removeMedia(int $id): void
    {
        $media = Media::findOrFail($id);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'book_id'         => $this->book->id,
            'user_id'         => auth()->id(),
            'title'           => $this->title,
            'slug'            => $this->slug,
            'excerpt'         => $this->excerpt,
            'body'            => $this->body,
            'primary_format'  => $this->primary_format,
            'status'          => $this->status,
            'visibility'      => $this->is_free_preview ? 'free' : $this->visibility,
            'token_price'     => $this->visibility === 'tokens' && !$this->is_free_preview
                ? $this->token_price : null,
            'is_free_preview' => $this->is_free_preview,
            'sort_order'      => $this->sort_order,
            'published_at'    => $this->status === 'published' ? now() : null,
        ];

        if ($this->isEditing) {
            $this->chapter->update($data);
            session()->flash('message', 'Chapter updated successfully.');
        } else {
            $this->chapter = Chapter::create($data);
            session()->flash('message', 'Chapter created successfully.');
        }

        $route = $this->isAdmin
            ? route('admin.chapters.edit', [$this->book, $this->chapter])
            : route('publish.chapters.edit', [$this->book, $this->chapter]);

        $this->redirect($route, navigate: true);
    }

}; ?>

<div>
    <div class="max-w-3xl mx-auto py-8 px-4 space-y-8">

        {{-- Page heading --}}
        <div class="flex items-center justify-between">
            <div>
                
                  <a  href="{{ $isAdmin ? route('admin.books.edit', $book) : route('publish.books.edit', $book) }}"
                    wire:navigate
                    class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1 mb-1"
                >
                    ← {{ $book->title }}
                </a>
                <flux:heading size="xl">
                    {{ $isEditing ? 'Edit chapter' : 'New chapter' }}
                </flux:heading>
            </div>
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
                <flux:input wire:model.live="title" placeholder="Chapter title..." />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="slug" />
                <flux:error name="slug" />
            </flux:field>

            <flux:field>
                <flux:label>Primary format</flux:label>
                <flux:select wire:model.live="primary_format">
                    <flux:select.option value="text">Text</flux:select.option>
                    <flux:select.option value="video">Video</flux:select.option>
                    <flux:select.option value="audio">Audio</flux:select.option>
                    <flux:select.option value="pdf">PDF</flux:select.option>
                </flux:select>
                <flux:error name="primary_format" />
            </flux:field>

            <flux:field>
                <flux:label>Excerpt</flux:label>
                <flux:textarea wire:model="excerpt" rows="2" placeholder="Short description..." />
                <flux:error name="excerpt" />
            </flux:field>

            <div class="flex items-center gap-4">
                <flux:field>
                    <flux:label>Sort order</flux:label>
                    <flux:input type="number" wire:model="sort_order" min="0" class="w-24" />
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:field>
                    <flux:label>Free preview</flux:label>
                    <flux:checkbox wire:model.live="is_free_preview" label="Make this chapter free to read" />
                </flux:field>
            </div>
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

        {{-- Step 2 — Content --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">02 — Content</p>

            @if($primary_format === 'text')
                <flux:field>
                    <flux:label>Body</flux:label>
                    <flux:textarea wire:model="body" rows="14" placeholder="Write your chapter..." />
                    <flux:error name="body" />
                </flux:field>
            @else
                <div class="space-y-3">
                    <flux:label>{{ ucfirst($primary_format) }} file</flux:label>

                    @if(!$isEditing)
                        <div class="p-4 bg-amber-50 border border-amber-100 rounded-lg">
                            <p class="text-sm text-amber-600">
                                Save the chapter first — then come back to upload media.
                            </p>
                        </div>
                    @else
                        @if($mediaProcessing)
                            <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                <p class="text-sm text-blue-600">
                                    Your file is being processed — this may take a moment.
                                </p>
                            </div>
                        @endif

                        @foreach($chapter->media()->ordered()->get() as $media)
                            <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                                <div class="flex items-center gap-3">
                                    @if($media->isVideo())
                                        <i class="ti ti-player-play text-zinc-400" aria-hidden="true"></i>
                                    @elseif($media->isAudio())
                                        <i class="ti ti-volume text-zinc-400" aria-hidden="true"></i>
                                    @elseif($media->isPdf())
                                        <i class="ti ti-file-text text-zinc-400" aria-hidden="true"></i>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $media->original_name }}
                                        </p>
                                        <p class="text-xs text-zinc-400">
                                            {{ $media->formattedSize() }}
                                            @if(!$media->is_processed && !$media->is_failed)
                                                · <span class="text-blue-400">processing...</span>
                                            @elseif($media->is_failed)
                                                · <span class="text-red-400">failed</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <flux:button
                                    wire:click="removeMedia({{ $media->id }})"
                                    wire:confirm="Remove this file?"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                />
                            </div>
                        @endforeach

                        <div wire:ignore>
                            <input
                                type="file"
                                @if($primary_format === 'video')
                                    accept="video/mp4,video/quicktime,video/x-msvideo"
                                @elseif($primary_format === 'audio')
                                    accept="audio/mpeg,audio/wav,audio/aac,audio/x-m4a"
                                @elseif($primary_format === 'pdf')
                                    accept="application/pdf"
                                @endif
                                x-init="
                                    initMediaPond($el, {
                                        wire: $wire,
                                        modelId: {{ $chapter->id }},
                                        modelType: 'chapter',
                                        format: '{{ $primary_format }}',
                                        multiple: false,
                                    })
                                "
                            />
                        </div>

                        <p class="text-xs text-zinc-400 mt-1">
                            @if($primary_format === 'video') MP4, MOV or AVI — max 500MB
                            @elseif($primary_format === 'audio') MP3, WAV, AAC or M4A — max 100MB
                            @elseif($primary_format === 'pdf') PDF — max 50MB
                            @endif
                        </p>
                    @endif
                </div>
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
                                modelId: {{ $chapter->id ?? 'null' }},
                                modelType: 'chapter',
                            })
                        "
                    />
                </div>
            </flux:field>

            @if(!$is_free_preview)
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
            @else
                <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-lg">
                    <p class="text-sm text-green-600">
                        This chapter is set as a free preview — access is open to all readers.
                    </p>
                </div>
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
        <div class="flex items-center justify-between pt-4 border-t border-zinc-100 dark:border-zinc-800">
            
              <a  href="{{ $isAdmin ? route('admin.books.edit', $book) : route('publish.books.edit', $book) }}"
                wire:navigate
                class="text-sm text-zinc-400 hover:text-zinc-600 transition-colors"
            >
                ← Back to book
            </a>
            <flux:button wire:click="save" wire:loading.attr="disabled" variant="primary">
                <span wire:loading.remove>{{ $isEditing ? 'Update chapter' : 'Save chapter' }}</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>

    </div>
</div>