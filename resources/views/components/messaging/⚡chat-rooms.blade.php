<?php

use App\Models\ChatRoom;
use App\Services\MessageService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public bool $showCreateForm = false;
    public string $roomName = '';
    public string $roomDescription = '';
    public string $roomContext = 'general';
    public bool $isPrivate = false;
    public ?int $activeRoomId = null;

    protected function rules(): array
    {
        return [
            'roomName'        => 'required|string|max:100',
            'roomDescription' => 'nullable|string|max:255',
            'roomContext'     => 'required|in:general,work',
            'isPrivate'       => 'boolean',
        ];
    }

    public function createRoom(): void
    {
        $this->validate();

        $result = app(MessageService::class)->createChatRoom(
            owner:       Auth::user(),
            name:        $this->roomName,
            context:     $this->roomContext,
            isPrivate:   $this->isPrivate,
            description: $this->roomDescription,
        );

        if ($result['success']) {
            $this->roomName        = '';
            $this->roomDescription = '';
            $this->showCreateForm  = false;
            $this->activeRoomId    = $result['room']->id;
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'rooms'     => $user->chatRooms()->with(['owner', 'lastMessage.sender'])->latest()->get(),
            'canCreate' => $user->canCreateChatRoom(),
            'maxRooms'  => $user->maxChatRooms(),
            'plan'      => $user->studioSubscription?->plan ?? 'free',
        ];
    }

}; ?>

<div>
    {{-- Toggle --}}
    <div class="flex gap-2 mb-4">
        <flux:button href="{{ route('messages.inbox') }}" variant="outline" size="sm">
            <flux:icon.inbox class="size-4 mr-1" /> Inbox
        </flux:button>
        <flux:button href="{{ route('messages.chat-rooms') }}" variant="primary" size="sm">
            <flux:icon.chat-bubble-left-right class="size-4 mr-1" /> Chat rooms
        </flux:button>
    </div>

    <div class="flex h-[calc(100vh-10rem)] border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">

        {{-- Sidebar --}}
        <div class="
            border-r border-zinc-200 dark:border-zinc-700 flex flex-col
            w-16 sm:w-72
            {{ $activeRoomId ? 'hidden sm:flex' : 'flex' }}
        ">

            {{-- Header --}}
            <div class="p-3 sm:p-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <p class="hidden sm:block text-sm font-medium text-zinc-900 dark:text-white">Chat rooms</p>
                    @if($canCreate)
                        <button
                            wire:click="$set('showCreateForm', true)"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                        >
                            <flux:icon.plus class="size-4" />
                        </button>
                    @endif
                </div>
                <p class="hidden sm:block text-xs text-zinc-400 mt-1">
                    {{ $rooms->count() }} / {{ $maxRooms }} rooms
                    @if($plan === 'free')
                        · <a href="{{ route('studio.subscription') }}" class="underline">Upgrade</a>
                    @endif
                </p>
            </div>

            {{-- Create form --}}
            @if($showCreateForm)
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                    <flux:input wire:model="roomName" placeholder="Room name..." size="sm" />
                    <flux:input wire:model="roomDescription" placeholder="Description..." size="sm" />
                    <flux:select wire:model="roomContext" size="sm">
                        <flux:select.option value="general">General</flux:select.option>
                        @if(in_array($plan, ['basic', 'pro']))
                            <flux:select.option value="work">Work group</flux:select.option>
                        @endif
                    </flux:select>
                    <div class="flex items-center gap-2">
                        <flux:checkbox wire:model="isPrivate" />
                        <span class="text-xs text-zinc-500">Private</span>
                    </div>
                    <flux:error name="roomName" />
                    <div class="flex gap-2">
                        <flux:button wire:click="createRoom" size="sm" variant="primary" class="flex-1">Create</flux:button>
                        <flux:button wire:click="$set('showCreateForm', false)" size="sm" variant="ghost">Cancel</flux:button>
                    </div>
                </div>
            @endif

            {{-- Rooms list --}}
            <div class="flex-1 overflow-y-auto">
                @forelse($rooms as $room)
                    @php $unread = $room->unreadCount(Auth::id()); @endphp
                    <button
                        wire:click="$set('activeRoomId', {{ $room->id }})"
                        class="w-full flex items-center gap-3 p-3 sm:p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors text-left
                            {{ $activeRoomId === $room->id ? 'bg-zinc-50 dark:bg-zinc-800' : '' }}"
                    >
                        {{-- Icon --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                <flux:icon.chat-bubble-left-right class="size-5 text-zinc-400" />
                            </div>
                            @if($unread > 0)
                                <span class="sm:hidden absolute -top-1 -right-1 w-4 h-4 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs rounded-full flex items-center justify-center">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="hidden sm:flex flex-1 min-w-0 flex-col">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $room->name }}
                                </p>
                                @if($unread > 0)
                                    <span class="text-xs px-1.5 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full flex-shrink-0 ml-1">
                                        {{ $unread }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-400 truncate mt-0.5">
                                @if($room->lastMessage?->type === 'media')
                                    📎 Media
                                @else
                                    {{ $room->lastMessage?->body ?? 'No messages yet' }}
                                @endif
                            </p>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-12 px-4">
                        <flux:icon.chat-bubble-left-right class="size-8 text-zinc-200 dark:text-zinc-700 mx-auto mb-2" />
                        <p class="hidden sm:block text-xs text-zinc-400">No chat rooms yet</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Room area --}}
        <div class="flex-1 flex flex-col min-w-0">
            @if($activeRoomId)
                {{-- Mobile back button --}}
                <div class="sm:hidden flex items-center gap-2 px-4 py-2 border-b border-zinc-200 dark:border-zinc-700">
                    <button
                        wire:click="$set('activeRoomId', null)"
                        class="text-zinc-400 hover:text-zinc-600"
                    >
                        <flux:icon.arrow-left class="size-5" />
                    </button>
                </div>
                <livewire:messaging.chat-room
                    :room-id="$activeRoomId"
                    :key="'room-'.$activeRoomId"
                />
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center px-4">
                        <flux:icon.chat-bubble-left-right class="size-10 text-zinc-200 dark:text-zinc-700 mx-auto mb-3" />
                        <p class="text-sm text-zinc-400">Select a room to start chatting</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>