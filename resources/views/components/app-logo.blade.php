@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Ta-on-gaf" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-auto items-center justify-center rounded-md bg-accent-content text-accent-foreground px-2">
            <x-app-logo-icon class="size-auto fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Ta-on-gaf" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-auto items-center justify-center rounded-md bg-accent-content text-accent-foreground px-2">
            <x-app-logo-icon class="size-auto fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
