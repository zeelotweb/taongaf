<x-layouts::admin.adminbar :title="$title ??  'Edit-Books'">
    <flux:main>
     <livewire:admin.book-form :book="$book" />
    </flux:main>
</x-layouts::admin.adminbar>


