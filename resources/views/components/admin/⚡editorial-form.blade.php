<?php

use App\Models\Editorial;
use App\Models\Media;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public ?Editorial $editorial = null;

    public string $title = '';
    public string $slug = '';
    public string $excerpt = '';
    public string $body = '';
    public string $primary_format = 'text';
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
            'title'          => 'required|string|max:255',
            'slug'           => 'required|string|unique:editorials,slug,' . ($this->editorial?->id ?? 'NULL'),
            'excerpt'        => 'nullable|string|max:500',
            'body'           => 'nullable|string',
            'primary_format' => 'required|in:text,video,audio,pdf',
            'status'         => 'required|in:draft,published,archived',
            'visibility'     => 'required|in:free,tokens',
            'token_price'    => 'nullable|integer|min:1',
        ];
    }

    public function mount(Editorial $editorial): void
    {
        $this->isAdmin = request()->routeIs('admin.*');

        if ($editorial->exists) {
            $this->isEditing = true;
            $this->editorial = $editorial;
            $this->fill($editorial->only([
                'title', 'slug', 'excerpt', 'body',
                'primary_format', 'status', 'visibility', 'token_price'
            ]));
            $this->existing_cover = $editorial->cover_image ?? '';
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
            'user_id'        => auth()->id(),
            'title'          => $this->title,
            'slug'           => $this->slug,
            'excerpt'        => $this->excerpt,
            'body'           => $this->body,
            'primary_format' => $this->primary_format,
            'status'         => $this->status,
            'visibility'     => $this->visibility,
            'token_price'    => $this->visibility === 'tokens' ? $this->token_price : null,
            'published_at'   => $this->status === 'published' ? now() : null,
        ];

        if ($this->isEditing) {
            $this->editorial->update($data);
            session()->flash('message', 'Editorial updated successfully.');
        } else {
            $this->editorial = Editorial::create($data);
            session()->flash('message', 'Editorial created successfully.');
        }

        $route = $this->isAdmin
            ? route('admin.editorials.edit', $this->editorial)
            : route('publish.editorials.edit', $this->editorial);

        $this->redirect($route, navigate: true);
    }

}; ?>

<div>
    <div class="max-w-3xl mx-auto py-8 px-4 space-y-8">

        {{-- Page heading --}}
        <div class="flex items-center justify-between">
            <flux:heading size="xl">
                {{ $isEditing ? 'Edit editorial' : 'New editorial' }}
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
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                01 — Basic info
            </p>

            <flux:field>
                <flux:label>Title</flux:label>
                <flux:input wire:model.live="title" placeholder="Editorial title..." />
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
        </div>

        <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

        {{-- Step 2 — Content --}}
        <div class="space-y-4">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                02 — Content
            </p>

            @if($primary_format === 'text')
                <flux:field>
                    <flux:label>Body</flux:label>
                    <flux:textarea wire:model="body" rows="14" placeholder="Write your editorial..." />
                    <flux:error name="body" />
                </flux:field>
            @else
                <div class="space-y-3">
                    <flux:label>{{ ucfirst($primary_format) }} file</flux:label>

                    @if(!$isEditing)
                        <div class="p-4 bg-amber-50 border border-amber-100 rounded-lg">
                            <p class="text-sm text-amber-600">
                                Save the editorial first — then come back to upload your media.
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

                        @foreach($editorial->media()->ordered()->get() as $media)
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
                                @elseif($primary_format === 'image')
                                    accept="image/jpeg,image/png,image/webp,image/heic"
                                @endif
                                x-init="
                                    initMediaPond($el, {
                                        wire: $wire,
                                        modelId: {{ $editorial->id }},
                                        modelType: 'editorial',
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
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                03 — Cover & meta
            </p>

            <flux:field>
                <flux:label>Cover image</flux:label>
                @if($existing_cover)
                    <img
                        src="{{ Storage::url($existing_cover) }}"
                        class="h-32 rounded-lg object-cover mb-2"
                    />
                @endif
                <div wire:ignore>
                    <input
                        type="file"
                        class="md:max-w-1/4"
                        x-init="
                            initCoverPond($el, {
                                wire: $wire,
                                modelId: {{ $editorial->id ?? 'null' }},
                                modelType: 'editorial',
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
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                04 — Publish
            </p>

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
                <span wire:loading.remove>
                    {{ $isEditing ? 'Update editorial' : 'Save editorial' }}
                </span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>

    </div>
</div>