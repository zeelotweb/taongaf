<?php

use App\Models\Survey;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public function deleteSurvey(int $id): void
    {
        Survey::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
        session()->flash('message', 'Survey deleted.');
    }

    public function toggleStatus(int $id): void
    {
        $survey = Survey::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $survey->update([
            'status' => $survey->status === 'active' ? 'closed' : 'active',
        ]);
    }

    public function with(): array
    {
        return [
            'surveys' => Survey::where('user_id', Auth::id())
                ->withCount('responses')
                ->latest()
                ->get(),
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('studio.index') }}" wire:navigate
               class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
                ← Back to studio
            </a>
            <flux:heading size="xl">Surveys</flux:heading>
        </div>
        <flux:button
            href="{{ route('studio.surveys.create') }}"
            variant="primary"
            icon="plus"
        >
            New survey
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    @if($surveys->isEmpty())
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-clipboard text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400 mb-4">No surveys yet.</p>
            <a href="{{ route('studio.surveys.create') }}">
                <flux:button variant="primary">Create your first survey</flux:button>
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($surveys as $survey)
                <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $survey->title }}
                            </p>
                            @if($survey->description)
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $survey->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2">
                                <flux:badge size="sm"
                                    :color="$survey->status === 'active' ? 'green' : ($survey->status === 'draft' ? 'yellow' : 'zinc')">
                                    {{ ucfirst($survey->status) }}
                                </flux:badge>
                                <span class="text-xs text-zinc-400">
                                    {{ $survey->responses_count }} responses
                                </span>
                                <span class="text-xs text-zinc-400">
                                    Audience: {{ ucfirst($survey->audience) }}
                                </span>
                                @if($survey->ends_at)
                                    <span class="text-xs text-zinc-400">
                                        Ends {{ $survey->ends_at->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                wire:click="toggleStatus({{ $survey->id }})"
                                size="sm"
                                variant="outline"
                            >
                                {{ $survey->status === 'active' ? 'Close' : 'Activate' }}
                            </flux:button>
                            <flux:button
                                href="{{ route('studio.surveys.edit', $survey) }}"
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                            />
                            <flux:button
                                wire:click="deleteSurvey({{ $survey->id }})"
                                wire:confirm="Delete this survey and all responses?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                            />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>