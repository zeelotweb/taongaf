<?php

use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\ChatRoomMessage;
use App\Models\HiddenChatRoomMessage;
use App\Models\User;
use App\Services\MessageService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public int $roomId;
    public string $body = '';
    public ?int $draftMessageId = null;
    public bool $showMembers = false;
    public string $inviteSearch = '';

    public function mount(int $roomId): void
    {
        $this->roomId = $roomId;
        $this->markRead();
    }

    public function getListeners(): array
    {
        return [
            "echo-presence:chat-room.{$this->roomId},.chat.message" => 'handleNewMessage',
        ];
    }

    public function handleNewMessage(): void
    {
        $this->markRead();
        $this->dispatch('scroll-to-bottom');
    }

    public function markRead(): void
    {
        ChatRoomMember::where('chat_room_id', $this->roomId)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);
    }

public function prepareDraft(): void
{
    \Log::info('chat room prepareDraft called', ['roomId' => $this->roomId]);

    if ($this->draftMessageId) {
        $existing = ChatRoomMessage::find($this->draftMessageId);
        if ($existing && $existing->media()->count() === 0) {
            $existing->delete();
        }
    }

    $message = ChatRoomMessage::create([
        'chat_room_id' => $this->roomId,
        'sender_id'    => Auth::id(),
        'body'         => '',
        'type'         => 'media',
    ]);

    $this->draftMessageId = $message->id;

    ChatRoom::where('id', $this->roomId)
        ->update(['last_message_at' => now()]);

    $this->dispatch('draft-ready', id: $message->id);
}

    public function notifyUploadComplete(string $path, string $type): void
    {
        $this->draftMessageId = null;
        $this->dispatch('scroll-to-bottom');
    }

    public function send(): void
    {
        if (empty(trim($this->body))) return;

        app(MessageService::class)->sendChatRoomMessage(
            room:   ChatRoom::findOrFail($this->roomId),
            sender: Auth::user(),
            body:   $this->body,
            type:   'text',
        );

        $this->body = '';
        $this->dispatch('scroll-to-bottom');
    }

    public function deleteForEveryone(int $messageId): void
    {
        ChatRoomMessage::where('id', $messageId)
            ->where('sender_id', Auth::id())
            ->update(['is_deleted' => true, 'deleted_at' => now()]);
    }

    public function hideMessage(int $messageId): void
    {
        HiddenChatRoomMessage::firstOrCreate([
            'user_id'              => Auth::id(),
            'chat_room_message_id' => $messageId,
        ]);
    }

    public function unhideMessage(int $messageId): void
    {
        HiddenChatRoomMessage::where('user_id', Auth::id())
            ->where('chat_room_message_id', $messageId)
            ->delete();
    }

    public function addMember(int $userId): void
    {
        app(MessageService::class)->addMember(
            ChatRoom::findOrFail($this->roomId),
            User::findOrFail($userId)
        );
        $this->inviteSearch = '';
    }

    public function removeMember(int $userId): void
    {
        $room = ChatRoom::findOrFail($this->roomId);
        if ($room->owner_id !== Auth::id()) return;

        ChatRoomMember::where('chat_room_id', $this->roomId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function with(): array
    {
        $userId = Auth::id();

        $room = ChatRoom::with([
            'members.user',
            'messages' => fn($q) => $q
                ->with(['sender', 'media', 'hiddenBy'])
                ->orderBy('created_at'),
        ])->findOrFail($this->roomId);

        $searchResults = [];
        if (strlen($this->inviteSearch) >= 2) {
            $memberIds     = $room->members->pluck('user_id')->toArray();
            $searchResults = User::where('name', 'like', '%' . $this->inviteSearch . '%')
                ->whereNotIn('id', $memberIds)
                ->take(5)
                ->get();
        }

        return [
            'room'          => $room,
            'messages'      => $room->messages,
            'members'       => $room->members,
            'isOwner'       => $room->owner_id === $userId,
            'searchResults' => $searchResults,
            'userId'        => $userId,
        ];
    }

}; ?>

<div class="flex flex-col h-full">

    {{-- Header --}}
    <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
        <div>
            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $room->name }}</p>
            <p class="text-xs text-zinc-400">{{ $members->count() }} members</p>
        </div>
        <flux:button wire:click="$toggle('showMembers')" size="sm" variant="ghost">
            <flux:icon.users class="size-4" />
        </flux:button>
    </div>

    <div class="flex flex-1 overflow-hidden">

        {{-- Messages --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            <div
                class="flex-1 overflow-y-auto p-4 space-y-4"
                x-data
                x-on:scroll-to-bottom.window="$el.scrollTop = $el.scrollHeight"
                x-init="$el.scrollTop = $el.scrollHeight"
            >
                @foreach($messages as $message)
                    @php $isMine  = $message->sender_id === $userId; @endphp
                    @php $isHidden = $message->isHiddenBy($userId); @endphp

                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} gap-2 group"
                         x-data="{ open: false }">

                        @if(!$isMine)
                            <div class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center overflow-hidden mt-1">
                                @if($message->sender?->avatar_path)
                                    <img src="{{ Storage::url($message->sender->avatar_path) }}" class="w-full h-full object-cover" />
                                @else
                                    <span class="text-xs font-medium text-zinc-500">
                                        {{ strtoupper(substr($message->sender?->name ?? '?', 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <div class="max-w-xs lg:max-w-md space-y-1">

                            @if(!$isMine)
                                <a href="{{ route('profile.show', $message->sender) }}"
                                   class="text-xs text-zinc-400 hover:text-zinc-600">
                                    {{ $message->sender?->name }}
                                </a>
                            @endif

                            @if($isHidden)
                                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-100 dark:border-zinc-700">
                                    <flux:icon.eye-slash class="size-3.5 text-zinc-400" />
                                    <p class="text-xs text-zinc-400 italic">You hid this message</p>
                                    <button
                                        wire:click="unhideMessage({{ $message->id }})"
                                        class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-white underline ml-1"
                                    >
                                        Unhide
                                    </button>
                                </div>

                            @elseif($message->is_deleted)
                                <div class="px-3 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-800">
                                    <p class="text-xs text-zinc-400 italic">Message deleted</p>
                                </div>

                            @else
                                {{-- Media --}}
                                @foreach($message->media as $media)
                                    <div class="rounded-2xl overflow-hidden">
                                        @if($media->type === 'image')
                                            <img
                                                src="{{ Storage::url($media->path) }}"
                                                alt="{{ $media->original_name }}"
                                                class="max-w-full rounded-2xl {{ $isMine ? 'rounded-tr-sm' : 'rounded-tl-sm' }}"
                                                style="max-height: 300px; object-fit: cover;"
                                            />
                                        @elseif($media->type === 'video')
                                            <video controls
                                                class="max-w-full rounded-2xl {{ $isMine ? 'rounded-tr-sm' : 'rounded-tl-sm' }}"
                                                style="max-height: 300px;">
                                                <source src="{{ Storage::url($media->path) }}" type="{{ $media->mime_type }}" />
                                            </video>
                                        @elseif($media->type === 'audio')
                                            <div class="px-4 py-3 rounded-2xl {{ $isMine ? 'bg-zinc-900 dark:bg-white rounded-tr-sm' : 'bg-zinc-100 dark:bg-zinc-800 rounded-tl-sm' }}">
                                                <audio controls class="w-full max-w-xs">
                                                    <source src="{{ Storage::url($media->path) }}" type="{{ $media->mime_type }}" />
                                                </audio>
                                            </div>
                                        @else
                                            <a href="{{ Storage::url($media->path) }}" target="_blank"
                                               class="flex items-center gap-3 px-4 py-3 rounded-2xl {{ $isMine ? 'bg-zinc-900 dark:bg-white rounded-tr-sm' : 'bg-zinc-100 dark:bg-zinc-800 rounded-tl-sm' }}">
                                                <flux:icon.document class="size-5 {{ $isMine ? 'text-white dark:text-zinc-900' : 'text-zinc-400' }}" />
                                                <div>
                                                    <p class="text-xs font-medium {{ $isMine ? 'text-white dark:text-zinc-900' : 'text-zinc-900 dark:text-white' }} truncate max-w-32">
                                                        {{ $media->original_name }}
                                                    </p>
                                                    <p class="text-xs {{ $isMine ? 'text-zinc-300 dark:text-zinc-600' : 'text-zinc-400' }}">
                                                        {{ $media->formattedSize() }}
                                                    </p>
                                                </div>
                                            </a>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Text --}}
                                @if($message->body)
                                    <div class="rounded-2xl px-4 py-2.5 {{ $isMine
                                        ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-tr-sm'
                                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white rounded-tl-sm' }}">
                                        <p class="text-sm leading-relaxed">{{ $message->body }}</p>
                                    </div>
                                @endif

                                {{-- Actions --}}
                                <div class="flex items-center gap-2 px-1 {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <span class="text-xs text-zinc-300 dark:text-zinc-600">
                                        {{ $message->created_at->format('h:i A') }}
                                    </span>
                                    <div class="relative">
                                        <button
                                            x-on:click="open = !open"
                                            class="text-zinc-300 hover:text-zinc-500 transition-colors opacity-0 group-hover:opacity-100"
                                        >
                                            <flux:icon.ellipsis-horizontal class="size-4" />
                                        </button>
                                        <div
                                            x-show="open"
                                            x-on:click.outside="open = false"
                                            x-transition
                                            class="absolute {{ $isMine ? 'right-0' : 'left-0' }} bottom-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-lg py-1 w-44 z-10"
                                        >
                                            <button
                                                wire:click="hideMessage({{ $message->id }})"
                                                x-on:click="open = false"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left"
                                            >
                                                <flux:icon.eye-slash class="size-3.5" />
                                                Hide for me
                                            </button>
                                            @if($isMine)
                                                <div class="border-t border-zinc-100 dark:border-zinc-700 my-1"></div>
                                                <button
                                                    wire:click="deleteForEveryone({{ $message->id }})"
                                                    wire:confirm="Delete for everyone? This cannot be undone."
                                                    x-on:click="open = false"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-500 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left"
                                                >
                                                    <flux:icon.trash class="size-3.5" />
                                                    Delete for everyone
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Compose --}}
            <div class="border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex items-end gap-2 p-4">

                    {{-- Paperclip --}}
<div wire:ignore class="flex-shrink-0" x-data="{ pond: null }">
    <button
        type="button"
        wire:click="prepareDraft"
        class="w-9 h-9 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
    >
        <flux:icon.paper-clip class="size-5" />
    </button>
    <input
        type="file"
        x-init="
            pond = initChatRoomMediaPond($el, {
                onComplete: (path) => $wire.notifyUploadComplete(path, 'media'),
            })
        "
    />
</div>

                    <flux:textarea
                        wire:model="body"
                        placeholder="Message {{ $room->name }}..."
                        rows="1"
                        class="flex-1 resize-none rounded-2xl"
                        x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.send()"
                    />

                    <flux:button
                        wire:click="send"
                        wire:loading.attr="disabled"
                        variant="primary"
                        class="flex-shrink-0"
                    >
                        <flux:icon.paper-airplane class="size-4" />
                    </flux:button>
                </div>
                <p class="text-xs text-zinc-400 px-4 pb-3">Enter to send · Shift+Enter for new line</p>
            </div>
        </div>

        {{-- Members panel --}}
        @if($showMembers)
            <div class="w-56 border-l border-zinc-200 dark:border-zinc-700 flex flex-col">
                <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                        Members ({{ $members->count() }})
                    </p>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    @foreach($members as $member)
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center overflow-hidden">
                                    @if($member->user->avatar_path)
                                        <img src="{{ Storage::url($member->user->avatar_path) }}" class="w-full h-full object-cover" />
                                    @else
                                        <span class="text-xs font-medium text-zinc-500">
                                            {{ strtoupper(substr($member->user->name, 0, 2)) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('profile.show', $member->user) }}"
                                       class="text-xs font-medium text-zinc-900 dark:text-white hover:underline">
                                        {{ $member->user->name }}
                                    </a>
                                    @if($member->role === 'owner')
                                        <p class="text-xs text-zinc-400">Owner</p>
                                    @endif
                                </div>
                            </div>
                            @if($isOwner && $member->user_id !== $userId)
                                <button
                                    wire:click="removeMember({{ $member->user_id }})"
                                    wire:confirm="Remove this member?"
                                    class="text-zinc-300 hover:text-red-400 transition-colors"
                                >
                                    <flux:icon.x-mark class="size-3" />
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($isOwner)
                    <div class="p-3 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                        <flux:input wire:model.live="inviteSearch" placeholder="Add member..." size="sm" />
                        @foreach($searchResults as $user)
                            <button
                                wire:click="addMember({{ $user->id }})"
                                class="w-full flex items-center gap-2 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 text-left"
                            >
                                <span class="text-xs text-zinc-900 dark:text-white">{{ $user->name }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>