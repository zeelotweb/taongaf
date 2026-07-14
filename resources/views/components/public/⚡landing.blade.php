<?php

use App\Models\Editorial;
use App\Models\Book;
use Livewire\Component;

new class extends Component {

    public function with(): array
    {
        return [
            'featuredEditorials' => Editorial::published()
                ->latest('published_at')
                ->take(4)
                ->get(),
            'featuredBooks' => Book::published()
                ->latest('published_at')
                ->take(3)
                ->get(),
        ];
    }

}; ?>

<div>
    {{-- Hero --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="max-w-2xl">
            <p class="text-xs text-zinc-400 uppercase tracking-widest mb-4">Ideas worth reading</p>
            <h1 class="text-4xl sm:text-5xl font-medium text-zinc-900 dark:text-white leading-tight tracking-tight mb-6">
                Editorials, books & voices that matter
            </h1>
            <p class="text-lg text-zinc-500 dark:text-zinc-400 leading-relaxed mb-8">
                A platform for writers, thinkers and publishers. Read in any format — text, video, audio or PDF. Publish your own and earn from your work.
            </p>
            <div class="flex gap-3">
                <a href="{{ route('editorials') }}"
                   class="px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-medium rounded-lg hover:bg-zinc-700 dark:hover:bg-zinc-100 transition-colors">
                    Start reading
                </a>
                @guest
                    <a href="{{ route('register') }}"
                       class="px-6 py-3 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        Become a publisher
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

    {{-- Featured editorials --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-8">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Featured editorials</p>
            <a href="{{ route('editorials') }}"
               class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                View all →
            </a>
        </div>

        @if($featuredEditorials->isEmpty())
            <p class="text-sm text-zinc-400">No editorials published yet.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredEditorials as $editorial)
                    <div class="group">
                        {{-- Cover --}}
                        <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden mb-3">
                            @if($editorial->cover_image)
                                <img
                                    src="{{ Storage::url($editorial->cover_image) }}"
                                    alt="{{ $editorial->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="ti ti-file-text text-2xl text-zinc-300" aria-hidden="true"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Meta --}}
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-zinc-400 uppercase tracking-wide">
                                {{ ucfirst($editorial->primary_format) }}
                            </span>
                            @if($editorial->visibility === 'tokens')
                                <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                                    {{ $editorial->token_price }} tokens
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full">
                                    Free
                                </span>
                            @endif
                        </div>

                        {{-- Title --}}
                        <h3 class="text-sm font-medium text-zinc-900 dark:text-white leading-snug mb-1 line-clamp-2">
                            {{ $editorial->title }}
                        </h3>

                        {{-- Excerpt --}}
                        @if($editorial->excerpt)
                            <p class="text-xs text-zinc-400 line-clamp-2 mb-3">{{ $editorial->excerpt }}</p>
                        @endif

                        {{-- CTA --}}
                        @auth
                            <a href="{{ route('editorial', $editorial->slug) }}"
                               class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Read editorial →
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                Sign in to read →
                            </a>
                        @endguest
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

    {{-- Featured books --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-8">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">Books</p>
            <a href="{{ route('books') }}"
               class="text-xs text-zinc-400 hover:text-zinc-600 flex items-center gap-1">
                Browse library →
            </a>
        </div>

        @if($featuredBooks->isEmpty())
            <p class="text-sm text-zinc-400">No books published yet.</p>
        @else
            <div class="grid sm:grid-cols-3 gap-6">
                @foreach($featuredBooks as $book)
                    <div class="group border border-zinc-100 dark:border-zinc-800 rounded-xl overflow-hidden hover:border-zinc-200 dark:hover:border-zinc-700 transition-colors">
                        {{-- Cover --}}
                        <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            @if($book->cover_image)
                                <img
                                    src="{{ Storage::url($book->cover_image) }}"
                                    alt="{{ $book->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="ti ti-book text-2xl text-zinc-300" aria-hidden="true"></i>
                                </div>
                            @endif
                        </div>

                        <div class="p-4">
                            <p class="text-xs text-zinc-400 mb-1">
                                {{ ucfirst(str_replace('_', ' ', $book->genre)) }}
                            </p>
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-white mb-1 line-clamp-2">
                                {{ $book->title }}
                            </h3>
                            @if($book->synopsis)
                                <p class="text-xs text-zinc-400 line-clamp-2 mb-3">{{ $book->synopsis }}</p>
                            @endif
                            <div class="flex items-center justify-between">
                                @if($book->visibility === 'tokens')
                                    <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-full">
                                        {{ $book->token_price }} tokens
                                    </span>
                                @else
                                    <span class="text-xs px-2 py-0.5 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full">
                                        Free
                                    </span>
                                @endif
                                @auth
                                    <a href="{{ route('book', $book->slug) }}"
                                       class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                        Read →
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                       class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">
                                        Sign in →
                                    </a>
                                @endguest
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

    {{-- Community CTA --}}
    <section id="community" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center max-w-xl mx-auto">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">Community</p>
            <h2 class="text-3xl font-medium text-zinc-900 dark:text-white tracking-tight mb-4">
                Publish your voice
            </h2>
            <p class="text-zinc-500 dark:text-zinc-400 mb-8">
                Share your editorials, books and ideas with the world. Set your own price and earn from your content.
            </p>
            @guest
                <a href="{{ route('register') }}"
                   class="px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-medium rounded-lg hover:bg-zinc-700 transition-colors">
                    Join as a publisher
                </a>
            @else
                <a href="{{ route('dashboard') }}"
                   class="px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-medium rounded-lg hover:bg-zinc-700 transition-colors">
                    Go to dashboard
                </a>
            @endguest
        </div>
    </section>
</div>