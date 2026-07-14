<?php

use App\Models\Comment;
use App\Models\Response;
use App\Models\Reply;
use Livewire\Component;
use Illuminate\Database\Eloquent\Model;

new class extends Component {

    public Model $model;

    // Comment form
    public string $commentBody = '';

    // Response forms — keyed by comment id
    public array $responseBody = [];
    public array $showResponseForm = [];

    // Reply forms — keyed by response id
    public array $replyBody = [];
    public array $showReplyForm = [];

    public function mount(Model $model): void
    {
        $this->model = $model;
    }

    public function with(): array
    {
        return [
            'comments' => $this->model->comments()
                ->with([
                    'user',
                    'votes',
                    'responses.user',
                    'responses.votes',
                    'responses.replies.user',
                    'responses.replies.votes',
                ])
                ->latest()
                ->get(),
        ];
    }

    // --- Comments ---

    public function addComment(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $this->validate(['commentBody' => 'required|string|min:1|max:1000']);

        Comment::create([
            'user_id'          => auth()->id(),
            'commentable_type' => get_class($this->model),
            'commentable_id'   => $this->model->id,
            'body'             => $this->commentBody,
        ]);

        $this->commentBody = '';
    }

    public function deleteComment(int $id): void
    {
        $comment = Comment::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $comment->delete();
    }

    // --- Responses ---

    public function toggleResponseForm(int $commentId): void
    {
        $this->showResponseForm[$commentId] = !($this->showResponseForm[$commentId] ?? false);
    }

    public function addResponse(int $commentId): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $this->validate(['responseBody.' . $commentId => 'required|string|min:1|max:1000']);

        Response::create([
            'user_id'    => auth()->id(),
            'comment_id' => $commentId,
            'body'       => $this->responseBody[$commentId],
        ]);

        $this->responseBody[$commentId]    = '';
        $this->showResponseForm[$commentId] = false;
    }

    public function deleteResponse(int $id): void
    {
        $response = Response::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $response->delete();
    }

    // --- Replies ---

    public function toggleReplyForm(int $responseId): void
    {
        $this->showReplyForm[$responseId] = !($this->showReplyForm[$responseId] ?? false);
    }

    public function addReply(int $responseId): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $this->validate(['replyBody.' . $responseId => 'required|string|min:1|max:500']);

        Reply::create([
            'user_id'     => auth()->id(),
            'response_id' => $responseId,
            'body'        => $this->replyBody[$responseId],
        ]);

        $this->replyBody[$responseId]    = '';
        $this->showReplyForm[$responseId] = false;
    }

    public function deleteReply(int $id): void
    {
        $reply = Reply::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $reply->delete();
    }

}; ?>

<div class="space-y-6">

    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
        Comments ({{ $comments->count() }})
    </p>

    {{-- Add comment --}}
    @auth
        <div class="flex gap-3">
            <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 flex items-center justify-center">
                @if(auth()->user()->avatar_path)
                    <img src="{{ Storage::url(auth()->user()->avatar_path) }}" class="w-full h-full rounded-full object-cover" />
                @else
                    <span class="text-xs font-medium text-zinc-500">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                @endif
            </div>
            <div class="flex-1">
                <flux:textarea
                    wire:model="commentBody"
                    rows="2"
                    placeholder="Add a comment..."
                    class="mb-2"
                />
                <flux:button wire:click="addComment" size="sm" variant="primary">
                    Post comment
                </flux:button>
            </div>
        </div>
    @else
        <p class="text-sm text-zinc-400">
            <a href="{{ route('login') }}" class="text-zinc-600 hover:underline">Sign in</a>
            to join the conversation.
        </p>
    @endauth

    {{-- Comments list --}}
    @forelse($comments as $comment)
        <div class="flex gap-3">
            {{-- Avatar --}}
            <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 flex items-center justify-center">
                @if($comment->user->avatar_path)
                    <img src="{{ Storage::url($comment->user->avatar_path) }}" class="w-full h-full rounded-full object-cover" />
                @else
                    <span class="text-xs font-medium text-zinc-500">
                        <a href="{{ route('profile.show', $comment->user) }}">
                        {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                    </a>
                    </span>
                @endif
            </div>

            <div class="flex-1 space-y-2">
                {{-- Comment body --}}
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-medium text-zinc-900 dark:text-white">
                            <a href="{{ route('profile.show', $comment->user) }}">
                            {{ $comment->user->name }}
                        </a>
                        </p>
                        <p class="text-xs text-zinc-400">
                            {{ $comment->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $comment->body }}</p>
                </div>

                {{-- Comment actions --}}
                <div class="flex items-center gap-3">
                    <livewire:interactions.vote :model="$comment" :key="'comment-vote-'.$comment->id" />

                    <button
                        wire:click="toggleResponseForm({{ $comment->id }})"
                        class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors"
                    >
                        Reply
                    </button>

                    @if(auth()->check() && auth()->id() === $comment->user_id)
                        <button
                            wire:click="deleteComment({{ $comment->id }})"
                            wire:confirm="Delete this comment?"
                            class="text-xs text-red-400 hover:text-red-600 transition-colors"
                        >
                            Delete
                        </button>
                    @endif
                </div>

                {{-- Response form --}}
                @if($showResponseForm[$comment->id] ?? false)
                    <div class="flex gap-2 mt-2">
                        <flux:textarea
                            wire:model="responseBody.{{ $comment->id }}"
                            rows="2"
                            placeholder="Write a response..."
                            class="flex-1"
                        />
                        <div class="flex flex-col gap-1">
                            <flux:button wire:click="addResponse({{ $comment->id }})" size="sm" variant="primary">
                                Post
                            </flux:button>
                            <flux:button wire:click="toggleResponseForm({{ $comment->id }})" size="sm" variant="ghost">
                                Cancel
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Responses --}}
                @foreach($comment->responses as $response)
                    <div class="flex gap-3 ml-4 mt-2">
                        <div class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 flex items-center justify-center">
                            @if($response->user->avatar_path)
                                <img src="{{ Storage::url($response->user->avatar_path) }}" class="w-full h-full rounded-full object-cover" />
                            @else
                                <span class="text-xs font-medium text-zinc-500">
                                    <a href="{{ route('profile.show', $response->user) }}">
                                    {{ strtoupper(substr($response->user->name, 0, 2)) }}
                                </a>
                                </span>
                            @endif
                        </div>

                        <div class="flex-1 space-y-2">
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-xs font-medium text-zinc-900 dark:text-white">
                                        <a href="{{ route('profile.show', $response->user) }}">
                                        {{ $response->user->name }}
                                    </a>
                                    </p>
                                    <p class="text-xs text-zinc-400">
                                        {{ $response->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $response->body }}</p>
                            </div>

                            {{-- Response actions --}}
                            <div class="flex items-center gap-3">
                                <livewire:interactions.vote :model="$response" :key="'response-vote-'.$response->id" />

                                <button
                                    wire:click="toggleReplyForm({{ $response->id }})"
                                    class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors"
                                >
                                    Reply
                                </button>

                                @if(auth()->check() && auth()->id() === $response->user_id)
                                    <button
                                        wire:click="deleteResponse({{ $response->id }})"
                                        wire:confirm="Delete this response?"
                                        class="text-xs text-red-400 hover:text-red-600 transition-colors"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </div>

                            {{-- Reply form --}}
                            @if($showReplyForm[$response->id] ?? false)
                                <div class="flex gap-2 mt-2">
                                    <flux:textarea
                                        wire:model="replyBody.{{ $response->id }}"
                                        rows="2"
                                        placeholder="Write a reply..."
                                        class="flex-1"
                                    />
                                    <div class="flex flex-col gap-1">
                                        <flux:button wire:click="addReply({{ $response->id }})" size="sm" variant="primary">
                                            Post
                                        </flux:button>
                                        <flux:button wire:click="toggleReplyForm({{ $response->id }})" size="sm" variant="ghost">
                                            Cancel
                                        </flux:button>
                                    </div>
                                </div>
                            @endif

                            {{-- Replies --}}
                            @foreach($response->replies as $reply)
                                <div class="flex gap-3 ml-4 mt-2">
                                    <div class="w-6 h-6 rounded-full bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 flex items-center justify-center">
                                        @if($reply->user->avatar_path)
                                            <img src="{{ Storage::url($reply->user->avatar_path) }}" class="w-full h-full rounded-full object-cover" />
                                        @else
                                            <span class="text-xs font-medium text-zinc-500">
                                                <a href="{{ route('profile.show', $reply->user) }}">
                                                {{ strtoupper(substr($reply->user->name, 0, 2)) }}
                                                </a>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex-1 space-y-2">
                                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-3">
                                            <div class="flex items-center justify-between mb-1">
                                                <p class="text-xs font-medium text-zinc-900 dark:text-white">
                                                    <a href="{{ route('profile.show', $reply->user) }}">
                                                    {{ $reply->user->name }}
                                                </a>
                                                </p>
                                                <p class="text-xs text-zinc-400">
                                                    {{ $reply->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $reply->body }}</p>
                                        </div>

                                        {{-- Reply actions — vote only, thread ends here --}}
                                        <div class="flex items-center gap-3">
                                            <livewire:interactions.vote :model="$reply" :key="'reply-vote-'.$reply->id" />

                                            @if(auth()->check() && auth()->id() === $reply->user_id)
                                                <button
                                                    wire:click="deleteReply({{ $reply->id }})"
                                                    wire:confirm="Delete this reply?"
                                                    class="text-xs text-red-400 hover:text-red-600 transition-colors"
                                                >
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-zinc-400 text-center py-8">
            No comments yet. Be the first to comment.
        </p>
    @endforelse

</div>