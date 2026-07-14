<x-layouts::admin.adminbar :title="$title ??  'AdminPanel'">
    <flux:main>
        {{ $slot }}
        @include('footer')
    </flux:main>
</x-layouts::admin.adminbar>


