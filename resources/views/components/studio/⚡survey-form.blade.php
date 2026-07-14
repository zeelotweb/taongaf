<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public ?Survey $survey = null;
    public bool $isEditing = false;

    // Survey fields
    public string $title = '';
    public string $description = '';
    public string $status = 'draft';
    public string $audience = 'all';
    public string $starts_at = '';
    public string $ends_at = '';

    // Questions
    public array $questions = [];

    protected function rules(): array
    {
        return [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'status'        => 'required|in:draft,active,closed',
            'audience'      => 'required|in:all,subscribers,approved',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date|after:starts_at',
            'questions'     => 'required|array|min:1',
            'questions.*.question'    => 'required|string',
            'questions.*.type'        => 'required|in:text,multiple_choice,rating,yes_no',
            'questions.*.is_required' => 'boolean',
        ];
    }

    public function mount(Survey $survey): void
    {
        if ($survey->exists) {
            $this->isEditing  = true;
            $this->survey     = $survey;
            $this->title       = $survey->title;
            $this->description = $survey->description ?? '';
            $this->status      = $survey->status;
            $this->audience    = $survey->audience;
            $this->starts_at   = $survey->starts_at?->format('Y-m-d') ?? '';
            $this->ends_at     = $survey->ends_at?->format('Y-m-d') ?? '';

            $this->questions = $survey->questions->map(fn($q) => [
                'question'    => $q->question,
                'type'        => $q->type,
                'options'     => $q->options ?? [],
                'is_required' => $q->is_required,
                'sort_order'  => $q->sort_order,
            ])->toArray();
        } else {
            $this->addQuestion();
        }
    }

    public function addQuestion(): void
    {
        $this->questions[] = [
            'question'    => '',
            'type'        => 'text',
            'options'     => [],
            'is_required' => true,
            'sort_order'  => count($this->questions),
        ];
    }

    public function removeQuestion(int $index): void
    {
        array_splice($this->questions, $index, 1);
    }

    public function addOption(int $questionIndex): void
    {
        $this->questions[$questionIndex]['options'][] = '';
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        array_splice($this->questions[$questionIndex]['options'], $optionIndex, 1);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id'     => Auth::id(),
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'audience'    => $this->audience,
            'starts_at'   => $this->starts_at ?: null,
            'ends_at'     => $this->ends_at ?: null,
        ];

        if ($this->isEditing) {
            $this->survey->update($data);
            $this->survey->questions()->delete();
        } else {
            $this->survey = Survey::create($data);
        }

        foreach ($this->questions as $index => $q) {
            SurveyQuestion::create([
                'survey_id'   => $this->survey->id,
                'question'    => $q['question'],
                'type'        => $q['type'],
                'options'     => $q['options'] ?? [],
                'is_required' => $q['is_required'],
                'sort_order'  => $index,
            ]);
        }

        session()->flash('message', 'Survey saved.');
        $this->redirect(route('studio.surveys'), navigate: true);
    }

}; ?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('studio.surveys') }}" wire:navigate
           class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
            ← Back to surveys
        </a>
        <flux:heading size="xl">
            {{ $isEditing ? 'Edit survey' : 'New survey' }}
        </flux:heading>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Basic info --}}
    <div class="space-y-4">
        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">01 — Basic info</p>

        <flux:field>
            <flux:label>Title</flux:label>
            <flux:input wire:model="title" placeholder="Survey title..." />
            <flux:error name="title" />
        </flux:field>

        <flux:field>
            <flux:label>Description</flux:label>
            <flux:textarea wire:model="description" rows="2" placeholder="Optional description..." />
            <flux:error name="description" />
        </flux:field>

        <div class="grid sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Audience</flux:label>
                <flux:select wire:model="audience">
                    <flux:select.option value="all">All community members</flux:select.option>
                    <flux:select.option value="subscribers">Subscribers only</flux:select.option>
                    <flux:select.option value="approved">Approved members only</flux:select.option>
                </flux:select>
                <flux:error name="audience" />
            </flux:field>

            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="closed">Closed</flux:select.option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Starts at (optional)</flux:label>
                <flux:input type="date" wire:model="starts_at" />
                <flux:error name="starts_at" />
            </flux:field>

            <flux:field>
                <flux:label>Ends at (optional)</flux:label>
                <flux:input type="date" wire:model="ends_at" />
                <flux:error name="ends_at" />
            </flux:field>
        </div>
    </div>

    <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

    {{-- Questions --}}
    <div class="space-y-4">
        <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">02 — Questions</p>

        @foreach($questions as $index => $question)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs text-zinc-400">Question {{ $index + 1 }}</p>
                    @if(count($questions) > 1)
                        <button
                            wire:click="removeQuestion({{ $index }})"
                            class="text-xs text-red-400 hover:text-red-600"
                        >
                            Remove
                        </button>
                    @endif
                </div>

                <flux:field>
                    <flux:input
                        wire:model="questions.{{ $index }}.question"
                        placeholder="Your question..."
                    />
                    <flux:error name="questions.{{ $index }}.question" />
                </flux:field>

                <div class="grid sm:grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model.live="questions.{{ $index }}.type">
                            <flux:select.option value="text">Text answer</flux:select.option>
                            <flux:select.option value="multiple_choice">Multiple choice</flux:select.option>
                            <flux:select.option value="rating">Rating (1-5)</flux:select.option>
                            <flux:select.option value="yes_no">Yes / No</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Required</flux:label>
                        <flux:checkbox
                            wire:model="questions.{{ $index }}.is_required"
                            label="This question is required"
                        />
                    </flux:field>
                </div>

                {{-- Multiple choice options --}}
                @if($question['type'] === 'multiple_choice')
                    <div class="space-y-2">
                        <p class="text-xs text-zinc-400">Options</p>
                        @foreach($question['options'] as $optIndex => $option)
                            <div class="flex items-center gap-2">
                                <flux:input
                                    wire:model="questions.{{ $index }}.options.{{ $optIndex }}"
                                    placeholder="Option {{ $optIndex + 1 }}"
                                    class="flex-1"
                                />
                                <button
                                    wire:click="removeOption({{ $index }}, {{ $optIndex }})"
                                    class="text-xs text-red-400 hover:text-red-600"
                                >
                                    <i class="ti ti-x" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endforeach
                        <flux:button
                            wire:click="addOption({{ $index }})"
                            size="sm"
                            variant="ghost"
                            icon="plus"
                        >
                            Add option
                        </flux:button>
                    </div>
                @endif
            </div>
        @endforeach

        <flux:button
            wire:click="addQuestion"
            variant="outline"
            icon="plus"
            class="w-full"
        >
            Add question
        </flux:button>

        <flux:error name="questions" />
    </div>

    {{-- Actions --}}
    <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
        <flux:button wire:click="save" wire:loading.attr="disabled" variant="primary">
            <span wire:loading.remove>{{ $isEditing ? 'Update survey' : 'Save survey' }}</span>
            <span wire:loading>Saving...</span>
        </flux:button>
    </div>

</div>