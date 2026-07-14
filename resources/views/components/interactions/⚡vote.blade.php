<?php

use App\Models\Vote;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

new class extends Component {

    public Model $model;
    public string $modelType = '';
    public int $upvotes = 0;
    public int $downvotes = 0;
    public ?string $userVote = null;

    public function mount(Model $model): void
    {
        $this->model     = $model;
        $this->modelType = class_basename($model);
        $this->loadVotes();
    }

    public function loadVotes(): void
    {
        $this->upvotes   = $this->model->votes()->where('type', 'up')->count();
        $this->downvotes = $this->model->votes()->where('type', 'down')->count();
        $this->userVote  = auth()->check()
            ? $this->model->votes()->where('user_id', auth()->id())->value('type')
            : null;
    }

    public function vote(string $type): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $existing = $this->model->votes()->where('user_id', auth()->id())->first();

        if ($existing) {
            if ($existing->type === $type) {
                // Toggle off
                $existing->delete();
            } else {
                // Switch vote
                $existing->update(['type' => $type]);
            }
        } else {
            Vote::create([
                'user_id'      => auth()->id(),
                'votable_type' => get_class($this->model),
                'votable_id'   => $this->model->id,
                'type'         => $type,
            ]);
        }

        $this->loadVotes();
    }

}; ?>

<div class="flex items-center gap-2">
    <button
        wire:click="vote('up')"
        class="flex items-center gap-1 px-2 py-1 rounded-lg text-xs transition-colors
            {{ $userVote === 'up'
                ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                : 'text-zinc-400 hover:text-green-500 hover:bg-green-50 dark:hover:bg-green-900/20' }}"
    >
        <i class="ti ti-thumb-up text-sm" aria-hidden="true"></i>
        <flux:icon.hand-thumb-up />
        {{ $upvotes }} 
    </button>

    <button
        wire:click="vote('down')"
        class="flex items-center gap-1 px-2 py-1 rounded-lg text-xs transition-colors
            {{ $userVote === 'down'
                ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                : 'text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20' }}"
    >
        <i class="ti ti-thumb-down text-sm" aria-hidden="true"></i>
        <flux:icon.hand-thumb-down />
        {{ $downvotes }} 
    </button>
</div>