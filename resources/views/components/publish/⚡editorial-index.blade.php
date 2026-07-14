<?php

use App\Models\Editorial;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function deleteEditorial(int $id): void
    {
        $editorial = Editorial::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $editorial->delete();
        session()->flash('message', 'Editorial deleted.');
    }

    public function with(): array
    {
        return [
            'editorials' => Editorial::where('user_id', auth()->id())
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(10),
        ];
    }

}; ?>

<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">My editorials</flux:heading>
        <flux:button href="{{ route('publish.editorials.create') }}" variant="primary" icon="plus">
            New editorial
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            {{ session('message') }}
        </flux:callout>
    @endif

    <div class="flex gap-3 mb-6">
        <flux:input wire:model.live="search" placeholder="Search..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="status" class="w-36">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="published">Published</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">Title</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">Format</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($editorials as $editorial)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $editorial->title }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="zinc">
                                {{ ucfirst($editorial->primary_format) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="$editorial->status === 'published' ? 'green' : ($editorial->status === 'archived' ? 'zinc' : 'yellow')">
                                {{ ucfirst($editorial->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-400 text-sm">
                            {{ $editorial->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-end">
                                <flux:button
                                    href="{{ route('publish.editorials.edit', $editorial) }}"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                />
                                <flux:button
                                    wire:click="deleteEditorial({{ $editorial->id }})"
                                    wire:confirm="Delete this editorial?"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-zinc-400">
                            No editorials yet.
                            <a href="{{ route('publish.editorials.create') }}" class="text-zinc-600 underline ml-1">
                                Create your first one.
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $editorials->links() }}</div>

</div>