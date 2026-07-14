<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

new class extends Component {

    public Model $model;
    public bool $showComments = true;
    public bool $showReactions = true;
    public bool $showBookmark = true;

}; ?>

<div class="border-t border-zinc-100 dark:border-zinc-800 mt-12 pt-8 space-y-8">

    {{-- Reactions + Bookmark row --}}
    @if($showReactions || $showBookmark)
        <div class="flex items-center justify-between">
            @if($showReactions)
                <livewire:interactions.reactions
                    :model="$model"
                    :key="'reactions-'.get_class($model).'-'.$model->id"
                />
            @endif

            @if($showBookmark)
                <livewire:interactions.bookmark
                    :model="$model"
                    :key="'bookmark-'.get_class($model).'-'.$model->id"
                />
            @endif
        </div>
    @endif

    {{-- Comments --}}
    @if($showComments)
        <livewire:interactions.comments
            :model="$model"
            :key="'comments-'.get_class($model).'-'.$model->id"
        />
    @endif

</div>