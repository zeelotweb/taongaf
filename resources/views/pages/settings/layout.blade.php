<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
        
{{-- Avatar --}}
<flux:modal.trigger name="update-avatar" class="bg-neutral-100 m-4">
    <div class="flex items-center gap-4 cursor-pointer group">
        <div class="w-16 h-16 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if(auth()->user()->avatar_path)
                <img
                    src="{{ Storage::url(auth()->user()->avatar_path) }}"
                    alt="{{ auth()->user()->name }}"
                    class="w-full h-full object-cover"
                />
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <span class="text-xl font-medium text-zinc-400">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                </div>
            @endif
        </div>
        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                {{ auth()->user()->name }}
            </p>
            <p class="text-xs text-zinc-400 group-hover:text-zinc-600 transition-colors">
                Click to update photo
            </p>
        </div>
    </div>
</flux:modal.trigger>


           
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>

</div>

{{-- Avatar modal --}}
<flux:modal name="update-avatar" class="w-full max-w-xl py-12 px-2"
                    flyout>
    <div class="p-6">
        <flux:heading size="lg" class="mb-1">Update profile picture</flux:heading>
        <p class="text-sm text-zinc-400 mb-6">
            JPG, PNG or WebP. Resized to 400x400 automatically.
        </p>
        <livewire:profile.avatar />
    </div>
</flux:modal>
