<x-layouts::admin.adminbar :title="$title ??  'Edit-Editorials'">
    <flux:main>
        <livewire:admin.editorial-form :editorial="$editorial" />
    </flux:main>
</x-layouts::admin.adminbar>


