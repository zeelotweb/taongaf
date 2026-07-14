<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
           @include('footer')
    </flux:main>
</x-layouts::app.sidebar>
