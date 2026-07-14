<?php

use App\Models\Bookmark;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

new class extends Component {

    public Model $model;
    public bool $isBookmarked = false;
    public int $count = 0;

    public function mount(Model $model): void
    {
        $this->model      = $model;
        $this->loadState();
    }

    public function loadState(): void
    {
        $this->count = $this->model->bookmarks()->count();

        $this->isBookmarked = auth()->check()
            ? $this->model->bookmarks()
                ->where('user_id', auth()->id())
                ->exists()
            : false;
    }

    public function toggle(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $existing = $this->model->bookmarks()
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Bookmark::create([
                'user_id'           => auth()->id(),
                'bookmarkable_type' => get_class($this->model),
                'bookmarkable_id'   => $this->model->id,
            ]);
        }

        $this->loadState();
    }

}; ?>

<button
    wire:click="toggle"
    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-colors
        {{ $isBookmarked
            ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
            : 'border border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-400 dark:hover:border-zinc-500' }}"
    title="{{ $isBookmarked ? 'Remove bookmark' : 'Bookmark' }}"
>
    <i class="ti {{ $isBookmarked ? 'ti-bookmark-filled' : 'ti-bookmark' }} text-sm" aria-hidden="true"></i>
    <span>{{ $isBookmarked ? 'Saved' : 'Save' }}</span>
    @if($count > 0)
        <span class="opacity-60">{{ $count }}</span>
    @endif
</button>