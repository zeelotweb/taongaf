<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public string $currentAvatar = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->currentAvatar = $user->avatar_path ?? '';
    }

    public function notifyUploadComplete(string $path, string $type): void
    {
        $this->currentAvatar = $path;
    }

}; ?>

<div class="flex items-center gap-6">

    {{-- Current avatar --}}
    <div class="relative">
        <div class="w-20 h-20 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 flex-shrink-0">
            @if($currentAvatar)
                <img
                    src="{{ Storage::url($currentAvatar) }}"
                    alt="{{ Auth::user()->name }}"
                    class="w-full h-full object-cover"
                />
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <span class="text-2xl font-medium text-zinc-400">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Upload --}}
    <div class="flex-1">
        <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1">Profile picture</p>
        <p class="text-xs text-zinc-400 mb-3">
            JPG, PNG or WebP. Resized to 400x400 automatically.
        </p>
        <div wire:ignore>
            <input
                type="file"
                x-init="
                    initProfilePond($el, {
                        wire: $wire,
                    })
                "
            />
        </div>
    </div>

</div>