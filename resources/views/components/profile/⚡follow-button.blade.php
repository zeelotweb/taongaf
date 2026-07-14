<?php

use App\Models\User;
use App\Models\CommunityMember;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public User $user;
    public bool $isFollowing = false;
    public string $status = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->loadState();
    }

    public function loadState(): void
    {
        if (!auth()->check()) return;

        $membership = CommunityMember::where('publisher_id', $this->user->id)
            ->where('user_id', Auth::id())
            ->first();

        $this->isFollowing = $membership && $membership->status === 'active';
        $this->status      = $membership?->status ?? '';
    }

    public function toggle(): void
    {
        if (!auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        $existing = CommunityMember::where('publisher_id', $this->user->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            $existing->delete();
            $this->isFollowing = false;
            $this->status      = '';
        } else {
            // Check community access type
            $settings   = $this->user->profileCommerceSetting;
            $accessType = 'open'; // default

            $member = CommunityMember::create([
                'publisher_id' => $this->user->id,
                'user_id'      => Auth::id(),
                'type'         => $accessType,
                'status'       => $accessType === 'open' ? 'active' : 'pending',
            ]);

            $this->isFollowing = $member->status === 'active';
            $this->status      = $member->status;
        }
    }

}; ?>

<button
    wire:click="toggle"
    class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-sm transition-colors
        {{ $isFollowing
            ? 'border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:border-red-300 hover:text-red-500'
            : 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 hover:bg-zinc-700' }}"
>
    @if($status === 'pending')
        <i class="ti ti-clock text-sm" aria-hidden="true"></i>
        Requested
    @elseif($isFollowing)
        <i class="ti ti-user-check text-sm" aria-hidden="true"></i>
        Following
    @else
        <i class="ti ti-user-plus text-sm" aria-hidden="true"></i>
        Follow
    @endif
</button>