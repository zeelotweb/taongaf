<?php

use App\Models\Reaction;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

new class extends Component {

    public Model $model;
    public array $counts = [];
    public ?string $userReaction = null;

    const EMOJIS = [
        'smile' => '😊',
        'fire'  => '🔥',
        'love'  => '❤️',
        'sad'   => '😢',
        'wow'   => '😮',
    ];

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->loadReactions();
    }

    public function loadReactions(): void
    {
        $reactions = $this->model->reactions()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $this->counts = $reactions;

        $this->userReaction = auth()->check()
            ? $this->model->reactions()
                ->where('user_id', auth()->id())
                ->value('type')
            : null;
    }

    public function react(string $type): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $existing = $this->model->reactions()
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();
            } else {
                $existing->update(['type' => $type]);
            }
        } else {
            Reaction::create([
                'user_id'       => auth()->id(),
                'reactable_type' => get_class($this->model),
                'reactable_id'  => $this->model->id,
                'type'          => $type,
            ]);
        }

        $this->loadReactions();
    }

}; ?>

<div class="flex items-center gap-1 flex-wrap">
    @foreach(self::EMOJIS as $type => $emoji)
        <button
            wire:click="react('{{ $type }}')"
            class="flex items-center gap-1 px-2.5 py-1 rounded-full text-sm transition-all
                {{ $userReaction === $type
                    ? 'bg-zinc-100 dark:bg-zinc-700 ring-1 ring-zinc-300 dark:ring-zinc-600'
                    : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
            title="{{ ucfirst($type) }}"
        >
            {{ $emoji }}
            @if(isset($counts[$type]) && $counts[$type] > 0)
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $counts[$type] }}
                </span>
            @endif
        </button>
    @endforeach
</div>