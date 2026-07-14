<x-layouts::admin.adminbar :title="$title ??  'Books'">
    <flux:main>
        <livewire:admin.chapter-form :book="$book" :chapter="$chapter" />
    </flux:main>
</x-layouts::admin.adminbar>


