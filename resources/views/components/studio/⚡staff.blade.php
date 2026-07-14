<?php

use App\Models\StudioMembership;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

new class extends Component {

    public string $inviteEmail = '';
    public array $inviteRoles = [];
    public bool $showInviteForm = false;

    const ROLES = [
        'content_manager'      => 'Content manager',
        'reaction_moderator'   => 'Reaction moderator',
        'community_moderator'  => 'Community moderator',
        'content_analyst'      => 'Content analyst',
    ];

    protected function rules(): array
    {
        return [
            'inviteEmail' => 'required|email|exists:users,email',
            'inviteRoles' => 'required|array|min:1',
        ];
    }

    public function invite(): void
    {
        $this->validate();

        $user = User::where('email', $this->inviteEmail)->first();

        // Check not already a member
        $exists = StudioMembership::where('publisher_id', Auth::id())
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            $this->addError('inviteEmail', 'This user is already in your studio.');
            return;
        }

        // Check plan limits
        $currentCount = StudioMembership::where('publisher_id', Auth::id())
            ->where('status', 'active')
            ->count();

        $plan = Auth::user()->studioSubscription?->plan;

        if ($plan === 'basic' && $currentCount >= 3) {
            $this->addError('inviteEmail', 'Basic plan allows up to 3 staff members. Upgrade to Pro for unlimited.');
            return;
        }

        StudioMembership::create([
            'publisher_id' => Auth::id(),
            'user_id'      => $user->id,
            'roles'        => $this->inviteRoles,
            'status'       => 'pending',
            'invite_token' => Str::random(32),
            'invited_at'   => now(),
        ]);

        $this->inviteEmail    = '';
        $this->inviteRoles    = [];
        $this->showInviteForm = false;

        session()->flash('message', 'Invitation sent to ' . $user->name);
    }

    public function updateRoles(int $membershipId, array $roles): void
    {
        StudioMembership::where('id', $membershipId)
            ->where('publisher_id', Auth::id())
            ->update(['roles' => $roles]);
    }

    public function removeMember(int $membershipId): void
    {
        StudioMembership::where('id', $membershipId)
            ->where('publisher_id', Auth::id())
            ->delete();

        session()->flash('message', 'Staff member removed.');
    }

    public function with(): array
    {
        return [
            'memberships' => StudioMembership::where('publisher_id', Auth::id())
                ->with('user')
                ->latest()
                ->get(),
            'plan' => Auth::user()->studioSubscription?->plan,
        ];
    }

}; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('studio.index') }}" wire:navigate
               class="text-xs text-zinc-400 hover:text-zinc-600 mb-1 block">
                ← Back to studio
            </a>
            <flux:heading size="xl">Staff</flux:heading>
        </div>
        <flux:button
            wire:click="$set('showInviteForm', true)"
            variant="primary"
            icon="plus"
        >
            Invite staff
        </flux:button>
    </div>

    @if(session()->has('message'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:callout>
    @endif

    {{-- Plan limit notice --}}
    @if($plan === 'basic')
        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-lg">
            <p class="text-xs text-amber-600">
                Basic plan — up to 3 staff members.
                <a href="{{ route('studio.subscription') }}" class="underline">Upgrade to Pro</a>
                for unlimited.
            </p>
        </div>
    @endif

    {{-- Invite form --}}
    @if($showInviteForm)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-6 space-y-4">
            <p class="text-sm font-medium text-zinc-900 dark:text-white">Invite a staff member</p>

            <flux:field>
                <flux:label>Email address</flux:label>
                <flux:input
                    wire:model="inviteEmail"
                    type="email"
                    placeholder="their@email.com"
                />
                <flux:error name="inviteEmail" />
            </flux:field>

            <div>
                <flux:label class="mb-2">Assign roles</flux:label>
                <div class="space-y-2">
                    @foreach(self::ROLES as $role => $label)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:model="inviteRoles"
                                value="{{ $role }}"
                                class="rounded border-zinc-300"
                            />
                            <div>
                                <p class="text-sm text-zinc-900 dark:text-white">{{ $label }}</p>
                                <p class="text-xs text-zinc-400">
                                    @if($role === 'content_manager')
                                        Create, edit and upload content
                                    @elseif($role === 'reaction_moderator')
                                        Moderate comments and reactions
                                    @elseif($role === 'community_moderator')
                                        Manage community members and requests
                                    @elseif($role === 'content_analyst')
                                        View analytics and send surveys
                                    @endif
                                </p>
                            </div>
                        </label>
                    @endforeach
                </div>
                <flux:error name="inviteRoles" />
            </div>

            <div class="flex gap-3">
                <flux:button wire:click="invite" variant="primary">Send invite</flux:button>
                <flux:button wire:click="$set('showInviteForm', false)" variant="ghost">Cancel</flux:button>
            </div>
        </div>
    @endif

    {{-- Staff list --}}
    @if($memberships->isEmpty())
        <div class="text-center py-16 border border-zinc-100 dark:border-zinc-800 rounded-xl">
            <i class="ti ti-users text-4xl text-zinc-200 dark:text-zinc-700 mb-3 block" aria-hidden="true"></i>
            <p class="text-sm text-zinc-400">No staff yet.</p>
            <p class="text-xs text-zinc-300 dark:text-zinc-600 mt-1">
                Invite team members to help manage your studio.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($memberships as $membership)
                <div class="border border-zinc-100 dark:border-zinc-800 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center">
                                @if($membership->user->avatar_path)
                                    <img
                                        src="{{ Storage::url($membership->user->avatar_path) }}"
                                        class="w-full h-full rounded-full object-cover"
                                    />
                                @else
                                    <span class="text-sm font-medium text-zinc-500">
                                        {{ strtoupper(substr($membership->user->name, 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $membership->user->name }}
                                </p>
                                <p class="text-xs text-zinc-400">{{ $membership->user->email }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:badge size="sm"
                                :color="$membership->status === 'active' ? 'green' : ($membership->status === 'pending' ? 'yellow' : 'zinc')">
                                {{ ucfirst($membership->status) }}
                            </flux:badge>
                            <flux:button
                                wire:click="removeMember({{ $membership->id }})"
                                wire:confirm="Remove this staff member?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                            />
                        </div>
                    </div>

                    {{-- Roles --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($membership->roles ?? [] as $role)
                            <span class="text-xs px-2 py-0.5 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-full">
                                {{ self::ROLES[$role] ?? $role }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Pending invite link --}}
                    @if($membership->status === 'pending' && $membership->invite_token)
                        <div class="mt-3 p-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg flex items-center justify-between">
                            <p class="text-xs text-zinc-400 truncate">
                                Invite link: {{ route('studio.invite', $membership->invite_token) }}
                            </p>
                            <button
                                onclick="navigator.clipboard.writeText('{{ route('studio.invite', $membership->invite_token) }}')"
                                class="text-xs text-zinc-500 hover:text-zinc-900 flex-shrink-0 ml-2"
                            >
                                Copy
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>