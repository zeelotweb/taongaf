<?php

use App\Models\User;
use App\Models\Editorial;
use App\Models\Book;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public User $user;
    public string $tab = 'editorials';

    public function with(): array
    {
        $metrics = $this->user->publisherMetrics;

        $editorials = Editorial::where('user_id', $this->user->id)
            ->published()
            ->when($this->tab === 'editorials', fn($q) => $q)
            ->latest('published_at')
            ->paginate(9);

        $books = Book::where('user_id', $this->user->id)
            ->published()
            ->latest('published_at')
            ->paginate(9);

        return [
            'metrics'     => $metrics,
            'editorials'  => $editorials,
            'books'       => $books,
            'isOwn'       => auth()->check() && auth()->id() === $this->user->id,
            'canMessage'  => auth()->check()
                && auth()->id() !== $this->user->id
                && $this->user->canReceiveMessageFrom(auth()->user()),
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">

    {{-- Profile header --}}
    <div class="flex flex-col sm:flex-row items-start gap-6">

        {{-- Avatar --}}
        <div class="w-24 h-24 rounded-full bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 overflow-hidden flex items-center justify-center">
            @if($user->avatar_path)
                <img
                    src="{{ Storage::url($user->avatar_path) }}"
                    alt="{{ $user->name }}"
                    class="w-full h-full object-cover"
                />
            @else
                <span class="text-3xl font-medium text-zinc-400 w-24 h-24 rounded-full justify-center items-center">
                    <p class="text-center justify-center items-center h-full w-full bg-zinc-800">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                    </p>
                </span>
            @endif
        </div>

        {{-- Info --}}
        <div class="flex-1">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-medium text-zinc-900 dark:text-white">
                        {{ $user->name }}
                    </h1>
                    @if($user->bio)
                        <p class="text-zinc-500 dark:text-zinc-400 mt-1 max-w-md">
                            {{ $user->bio }}
                        </p>
                    @endif
                </div>

                {{-- Action buttons --}}
                @if(!$isOwn && auth()->check())
                    <div class="flex items-center gap-2">
                        {{-- Follow button --}}
                        <livewire:profile.follow-button
                            :user="$user"
                            :key="'follow-'.$user->id"
                        />

                        {{-- Message button --}}
                        @if($canMessage)
                            <livewire:profile.message-button
                                :user="$user"
                                :key="'message-'.$user->id"
                            />
                        @endif

                        {{-- Promote button --}}
                        @if($user->hasCommerceEnabled())
                            <a href="{{ route('hustle.promote', $user) }}">
                                <flux:button size="sm" variant="outline" icon="rocket">
                                    Promote
                                </flux:button>
                            </a>
                        @endif
                    </div>
                @elseif($isOwn)
                    <a href="{{ route('profile.edit') }}">
                        <flux:button size="sm" variant="outline" icon="pencil">
                            Edit profile
                        </flux:button>
                    </a>
                @else
                    {{-- Guest --}}
                    <a href="{{ route('login') }}">
                        <flux:button size="sm" variant="primary">
                            Sign in to follow
                        </flux:button>
                    </a>
                @endif
            </div>

            {{-- Stats --}}
            <div class="flex gap-6 mt-4">
                <div>
                    <p class="text-lg font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics?->content_count ?? 0) }}
                    </p>
                    <p class="text-xs text-zinc-400">Published</p>
                </div>
                <div>
                    <p class="text-lg font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics?->follower_count ?? 0) }}
                    </p>
                    <p class="text-xs text-zinc-400">Followers</p>
                </div>
                <div>
                    <p class="text-lg font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics?->total_views ?? 0) }}
                    </p>
                    <p class="text-xs text-zinc-400">Total views</p>
                </div>
                <div>
                    <p class="text-lg font-medium text-zinc-900 dark:text-white">
                        {{ number_format(($metrics?->engagement_rate ?? 0) * 100, 1) }}%
                    </p>
                    <p class="text-xs text-zinc-400">Engagement</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Studio badge --}}
    @if($user->hasActiveStudio())
        <div class="flex items-center gap-2 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg w-fit">
            <i class="ti ti-building-store text-zinc-400 text-sm" aria-hidden="true"></i>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Studio publisher</p>
            @if($user->studioSubscription?->plan === 'pro')
                <flux:badge size="sm" color="blue">Pro</flux:badge>
            @else
                <flux:badge size="sm" color="zinc">Basic</flux:badge>
            @endif
        </div>
    @endif

    {{-- Content tabs --}}
    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-6">
        <div class="flex gap-4 mb-6">
            @foreach(['editorials', 'books'] as $t)
                <button
                    wire:click="$set('tab', '{{ $t }}')"
                    class="text-sm transition-colors pb-2 border-b-2
                        {{ $tab === $t
                            ? 'border-zinc-900 dark:border-white text-zinc-900 dark:text-white font-medium'
                            : 'border-transparent text-zinc-400 hover:text-zinc-600' }}"
                >
                    {{ ucfirst($t) }}
                </button>
            @endforeach
        </div>

        {{-- Editorials tab --}}
        @if($tab === 'editorials')
            @if($editorials->isEmpty())
                <p class="text-sm text-zinc-400">No editorials published yet.</p>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($editorials as $editorial)
                        <a href="{{ route('editorial', $editorial->slug) }}"
                           class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">
                            <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                @if($editorial->cover_image)
                                    <img
                                        src="{{ Storage::url($editorial->cover_image) }}"
                                        alt="{{ $editorial->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-file-text text-zinc-300 text-xl" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white line-clamp-2">
                                    {{ $editorial->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <flux:badge size="sm" color="zinc">
                                        {{ ucfirst($editorial->primary_format) }}
                                    </flux:badge>
                                    @if($editorial->visibility === 'free')
                                        <flux:badge size="sm" color="green">Free</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="blue">
                                            {{ $editorial->token_price }} tokens
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4">{{ $editorials->links() }}</div>
            @endif
        @endif

        {{-- Books tab --}}
        @if($tab === 'books')
            @if($books->isEmpty())
                <p class="text-sm text-zinc-400">No books published yet.</p>
            @else
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($books as $book)
                        <a href="{{ route('book', $book->slug) }}"
                           class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors block">
                            <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                @if($book->cover_image)
                                    <img
                                        src="{{ Storage::url($book->cover_image) }}"
                                        alt="{{ $book->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="ti ti-book text-zinc-300 text-xl" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white line-clamp-2">
                                    {{ $book->title }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <flux:badge size="sm" color="zinc">
                                        {{ ucfirst(str_replace('_', ' ', $book->genre)) }}
                                    </flux:badge>
                                    @if($book->visibility === 'free')
                                        <flux:badge size="sm" color="green">Free</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="blue">
                                            {{ $book->token_price }} tokens
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-4">{{ $books->links() }}</div>
            @endif
        @endif
    </div>

</div>