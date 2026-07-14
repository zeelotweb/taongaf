<x-layouts::admin.adminbar :title="$title ??  'Editorials'">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::admin.adminbar>


