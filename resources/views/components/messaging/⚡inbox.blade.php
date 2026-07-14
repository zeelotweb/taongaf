<?php

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Services\MessageService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    public string $search = '';
    public string $filter = 'all';
    public ?int $activeConversationId = null;

    public function startConversation(int $userId): void
    {
        $recipient = \App\Models\User::findOrFail($userId);
        $service   = new MessageService();
        $result    = $service->getOrCreateConversation(Auth::user(), $recipient);

        if ($result['success']) {
            $this->activeConversationId = $result['conversation']->id;
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function archiveConversation(int $conversationId): void
    {
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', Auth::id())
            ->update(['is_archived' => true]);
    }

    public function unarchiveConversation(int $conversationId): void
    {
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', Auth::id())
            ->update(['is_archived' => false]);
    }

    public function deleteConversation(int $conversationId): void
    {
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', Auth::id())
            ->update(['deleted_at' => now()]);
    }

    public function with(): array
    {
        $userId = Auth::id();

        $conversations = Conversation::whereHas('participants', fn($q) =>
            $q->where('user_id', $userId)
              ->whereNull('deleted_at')
              ->when($this->filter === 'archived', fn($q) => $q->where('is_archived', true))
              ->when($this->filter === 'all', fn($q) => $q->where('is_archived', false))
        )
        ->with(['lastMessage.sender', 'participants.user'])
        ->when($this->search, fn($q) => $q->whereHas('participants.user', fn($u) =>
            $u->where('name', 'like', '%' . $this->search . '%')
              ->where('id', '!=', $userId)
        ))
        ->orderByDesc('last_message_at')
        ->get();

        $totalUnread = $conversations->sum(fn($c) => $c->unreadCount($userId));

        return [
            'conversations' => $conversations,
            'totalUnread'   => $totalUnread,
        ];
    }

}; ?>

<div>

    {{-- App\Models\Conversation::all() --}}
<br><br>
    {{-- App\Models\MessageAttachment::all() --}}
<br><br>
    {{-- App\Models\Media::all() --}}
    {{-- Toggle --}}
    <div class="flex gap-2 mb-4">
        <flux:button href="{{ route('messages.inbox') }}" variant="primary" size="sm">
            <flux:icon.inbox class="size-4 mr-1" /> Inbox
        </flux:button>
        <flux:button href="{{ route('messages.chat-rooms') }}" variant="outline" size="sm">
            <flux:icon.chat-bubble-left-right class="size-4 mr-1" /> Chat rooms
        </flux:button>
    </div>

    <div class="flex h-[calc(100vh-10rem)] border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">

        {{-- Sidebar --}}
        <div class="
            border-r border-zinc-200 dark:border-zinc-700 flex flex-col
            w-16 sm:w-80
            {{ $activeConversationId ? 'hidden sm:flex' : 'flex' }}
        ">

            {{-- Header --}}
            <div class="p-3 sm:p-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between mb-3">
                    <p class="hidden sm:block text-sm font-medium text-zinc-900 dark:text-white">
                        Messages
                        @if($totalUnread > 0)
                            <span class="ml-1 text-xs px-1.5 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full">
                                {{ $totalUnread }}
                            </span>
                        @endif
                    </p>
                    @if($totalUnread > 0)
                        <span class="sm:hidden text-xs px-1.5 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full">
                            {{ $totalUnread }}
                        </span>
                    @endif
                </div>

                {{-- Search — hidden on mobile --}}
                <div class="hidden sm:block">
                    <flux:input wire:model.live="search" placeholder="Search..." size="sm" />
                </div>

                {{-- Filters — hidden on mobile --}}
                <div class="hidden sm:flex gap-2 mt-2">
                    @foreach(['all', 'archived'] as $f)
                        <button
                            wire:click="$set('filter', '{{ $f }}')"
                            class="text-xs px-2 py-1 rounded-full transition-colors
                                {{ $filter === $f
                                    ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900'
                                    : 'text-zinc-400 hover:text-zinc-600' }}"
                        >
                            {{ ucfirst($f) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Conversations list --}}
            <div class="flex-1 overflow-y-auto">
                @forelse($conversations as $conversation)
                    @php
                        $other  = $conversation->getOtherParticipant(Auth::id());
                        $last   = $conversation->lastMessage;
                        $unread = $conversation->unreadCount(Auth::id());
                    @endphp
                    <button
                        wire:click="$set('activeConversationId', {{ $conversation->id }})"
                        class="w-full flex items-center gap-3 p-3 sm:p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors text-left
                            {{ $activeConversationId === $conversation->id ? 'bg-zinc-50 dark:bg-zinc-800' : '' }}"
                    >
                        {{-- Avatar --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden flex items-center justify-center">
                                @if($other?->avatar_path)
                                    <img src="{{ Storage::url($other->avatar_path) }}" class="w-full h-full object-cover" />
                                @else
                                    <span class="text-sm font-medium text-zinc-500">
                                        {{ strtoupper(substr($other?->name ?? '?', 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            @if($unread > 0)
                                <span class="sm:hidden absolute -top-1 -right-1 w-4 h-4 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-xs rounded-full flex items-center justify-center">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>

                        {{-- Info — hidden on mobile --}}
                        <div class="hidden sm:flex flex-1 min-w-0 flex-col">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $other?->name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-zinc-400 flex-shrink-0 ml-2">
                                    {{ $last?->created_at?->diffForHumans(short: true) }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-xs text-zinc-400 truncate">
                                    @if($last?->is_deleted)
                                        <span class="italic">Message deleted</span>
                                    @elseif($last?->type === 'forwarded')
                                        <span class="italic">Forwarded message</span>
                                    @elseif($last?->type === 'media')
                                        <span class="italic">📎 Media</span>
                                    @else
                                        {{ $last?->body ?? 'No messages yet' }}
                                    @endif
                                </p>
                                @if($unread > 0)
                                    <span class="text-xs px-1.5 py-0.5 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full flex-shrink-0 ml-1">
                                        {{ $unread }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-12 px-4">
                        <flux:icon.chat-bubble-left class="size-8 text-zinc-200 dark:text-zinc-700 mx-auto mb-2" />
                        <p class="hidden sm:block text-xs text-zinc-400">No conversations yet</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Conversation area --}}
        <div class="flex-1 flex flex-col min-w-0">
            @if($activeConversationId)
                {{-- Mobile back button --}}
                <div class="sm:hidden flex items-center gap-2 px-4 py-2 border-b border-zinc-200 dark:border-zinc-700">
                    <button
                        wire:click="$set('activeConversationId', null)"
                        class="text-zinc-400 hover:text-zinc-600"
                    >
                        <flux:icon.arrow-left class="size-5" />
                    </button>
                </div>
                <livewire:messaging.conversation
                    :conversation-id="$activeConversationId"
                    :key="'conv-'.$activeConversationId"
                />
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center px-4">
                        <flux:icon.chat-bubble-left-right class="size-10 text-zinc-200 dark:text-zinc-700 mx-auto mb-3" />
                        <p class="text-sm text-zinc-400">Select a conversation</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>