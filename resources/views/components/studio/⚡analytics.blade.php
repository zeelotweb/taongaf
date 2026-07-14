<?php

use App\Models\Editorial;
use App\Models\Book;
use App\Models\PublisherMetrics;
use App\Jobs\UpdatePublisherMetricsJob;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public bool $refreshing = false;

    public function refreshMetrics(): void
    {
        $this->refreshing = true;
        UpdatePublisherMetricsJob::dispatch(Auth::id());
        session()->flash('message', 'Metrics refresh queued — check back in a moment.');
        $this->refreshing = false;
    }

    public function with(): array
    {
        $user    = Auth::user();
        $metrics = $user->publisherMetrics;

        // Top performing editorials
        $topEditorials = Editorial::where('user_id', $user->id)
            ->published()
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        // Top performing books
        $topBooks = Book::where('user_id', $user->id)
            ->published()
            ->orderByDesc('views_count')
            ->take(5)
            ->get();

        return [
            'metrics'       => $metrics,
            'topEditorials' => $topEditorials,
            'topBooks'      => $topBooks,
            'lastUpdated'   => $metrics?->calculated_at?->diffForHumans() ?? 'Never',
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('studio.index') }}" wire:navigate
               class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
                ← Back to studio
            </a>
            <flux:heading size="xl">Analytics</flux:heading>
            <p class="text-xs text-zinc-400 mt-1">Last updated: {{ $lastUpdated }}</p>
        </div>
        <flux:button
            wire:click="refreshMetrics"
            wire:loading.attr="disabled"
            variant="outline"
            icon="arrow-path"
        >
            <span wire:loading.remove wire:target="refreshMetrics">Refresh</span>
            <span wire:loading wire:target="refreshMetrics">Refreshing...</span>
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if(!$metrics)
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-chart-bar text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400 mb-4">No metrics yet.</p>
            <flux:button wire:click="refreshMetrics" variant="primary">
                Generate metrics
            </flux:button>
        </div>
    @else

        {{-- Overview stats --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Total views</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($metrics->total_views) }}
                </p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Total reads</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($metrics->total_reads) }}
                </p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Engagement rate</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($metrics->engagement_rate * 100, 1) }}%
                </p>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs text-zinc-400 uppercase tracking-wider mb-1">Retention rate</p>
                <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                    {{ number_format($metrics->retention_rate, 1) }}%
                </p>
            </div>
        </div>

        {{-- Engagement breakdown --}}
        <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-6">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                Engagement breakdown
            </p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics->total_reactions) }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">Reactions</p>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics->total_comments) }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">Comments</p>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics->total_bookmarks) }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">Bookmarks</p>
                </div>
            </div>
        </div>

        {{-- Earnings --}}
        <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-6">
            <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                Token earnings
            </p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-xs text-green-600 mb-1">This month</p>
                    <p class="text-2xl font-medium text-green-700 dark:text-green-400">
                        {{ number_format($metrics->monthly_token_earnings) }}
                        <span class="text-sm font-normal">tokens</span>
                    </p>
                </div>
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <p class="text-xs text-zinc-400 mb-1">All time</p>
                    <p class="text-2xl font-medium text-zinc-900 dark:text-white">
                        {{ number_format($metrics->total_token_earnings) }}
                        <span class="text-sm font-normal text-zinc-400">tokens</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Top editorials --}}
        @if($topEditorials->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Top editorials by views
                </p>
                <div class="space-y-2">
                    @foreach($topEditorials as $editorial)
                        <div class="flex items-center justify-between p-3 border border-zinc-100 dark:border-zinc-800 rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 overflow-hidden">
                                    @if($editorial->cover_image)
                                        <img src="{{ Storage::url($editorial->cover_image) }}" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="ti ti-file-text text-zinc-300 text-xs" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-sm text-zinc-900 dark:text-white truncate">
                                    {{ $editorial->title }}
                                </p>
                            </div>
                            <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                                <span class="text-xs text-zinc-400">
                                    {{ number_format($editorial->views_count) }} views
                                </span>
                                <span class="text-xs text-zinc-400">
                                    {{ number_format($editorial->reads_count) }} reads
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Top books --}}
        @if($topBooks->isNotEmpty())
            <div>
                <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider mb-4">
                    Top books by views
                </p>
                <div class="space-y-2">
                    @foreach($topBooks as $book)
                        <div class="flex items-center justify-between p-3 border border-zinc-100 dark:border-zinc-800 rounded-lg">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded bg-zinc-100 dark:bg-zinc-800 flex-shrink-0 overflow-hidden">
                                    @if($book->cover_image)
                                        <img src="{{ Storage::url($book->cover_image) }}" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="ti ti-book text-zinc-300 text-xs" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-sm text-zinc-900 dark:text-white truncate">
                                    {{ $book->title }}
                                </p>
                            </div>
                            <div class="flex items-center gap-4 flex-shrink-0 ml-4">
                                <span class="text-xs text-zinc-400">
                                    {{ number_format($book->views_count) }} views
                                </span>
                                <span class="text-xs text-zinc-400">
                                    {{ number_format($book->reads_count) }} reads
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    @endif

</div>