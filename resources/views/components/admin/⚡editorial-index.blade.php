<?php

use Livewire\Component;

use App\Models\Editorial;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $format = '';
    public string $status = '';

public function deleteEditorial(int $id): void
{
    $this->authorize('delete-content');
    $editorial = Editorial::findOrFail($id);
    $editorial->delete();
    session()->flash('message', 'Editorial deleted.');
}

    public function with(): array
    {
        return [
            'editorials' => Editorial::query()
                ->where('user_id', auth()->id())
                ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%'))
                ->when($this->format, fn($q) => $q->where('primary_format', $this->format))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(10),
        ];
    }

}; ?>

<div>
{{-- Table --}}
<div class="border border-zinc-200 rounded-xl overflow-hidden bg-white">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 border-b border-zinc-200">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">Title</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">Format</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">Access</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">Status</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">Date</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($editorials as $editorial)
                <tr class="hover:bg-zinc-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-zinc-900">
                        {{ $editorial->title }}
                    </td>
                    <td class="px-4 py-3">
                        <flux:badge size="sm" color="zinc">
                            {{ ucfirst($editorial->primary_format) }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-3">
                        <flux:badge size="sm" :color="$editorial->visibility === 'free' ? 'green' : 'blue'">
                            {{ $editorial->visibility === 'free' ? 'Free' : $editorial->token_price . ' tokens' }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-3">
                        <flux:badge size="sm" :color="$editorial->status === 'published' ? 'green' : ($editorial->status === 'archived' ? 'zinc' : 'yellow')">
                            {{ ucfirst($editorial->status) }}
                        </flux:badge>
                    </td>
                    <td class="px-4 py-3 text-zinc-400">
                        {{ $editorial->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 justify-end">
                            <flux:button
                                href="{{ route('admin.editorials.edit', $editorial) }}"
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                            />
                            @can('delete-content')
                            <flux:button
                                wire:click="deleteEditorial({{ $editorial->id }})"
                                wire:confirm="Are you sure you want to delete this editorial?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                            />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-zinc-400">
                        No editorials yet.
                        <a href="{{ route('admin.editorials.create') }}" class="text-zinc-600 underline ml-1">
                            Create your first one.
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>