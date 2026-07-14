<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\HiddenMessage;
use App\Services\MessageService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {

    public int $conversationId;
    public string $body = '';
    public ?int $draftMessageId = null;
    public ?int $forwardingMessageId = null;
    public bool $showForwardModal = false;
    public int $forwardToUserId = 0;

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;
        $this->markRead();
    }

    public function getListeners(): array
    {
        return [
            "echo-private:conversation.{$this->conversationId},.message.sent" => 'handleNewMessage',
        ];
    }

    public function handleNewMessage(): void
    {
        $this->markRead();
        $this->dispatch('scroll-to-bottom');
    }

    public function markRead(): void
    {
        $conversation = Conversation::find($this->conversationId);
        if ($conversation) {
            app(MessageService::class)->markAsRead($conversation, Auth::user());
        }
    }

    public function prepareDraft(): void
    {
        // Clean up any existing empty draft first
        if ($this->draftMessageId) {
            $existing = Message::find($this->draftMessageId);
            if ($existing && $existing->media()->count() === 0) {
                $existing->delete();
            }
        }

        $message = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id'       => Auth::id(),
            'body'            => '',
            'type'            => 'media',
        ]);

        $this->draftMessageId = $message->id;

        Conversation::where('id', $this->conversationId)
            ->update(['last_message_at' => now()]);
    }

    public function notifyUploadComplete(string $path, string $type): void
    {
        $this->draftMessageId = null;
        $this->dispatch('scroll-to-bottom');
    }

    public function send(): void
    {
        if (empty(trim($this->body))) return;

        $result = app(MessageService::class)->sendMessage(
            conversation: Conversation::findOrFail($this->conversationId),
            sender:       Auth::user(),
            body:         $this->body,
        );

        if ($result['success']) {
            $this->body = '';
            $this->dispatch('scroll-to-bottom');
        }
    }

    public function deleteForEveryone(int $messageId): void
    {
        Message::where('id', $messageId)
            ->where('sender_id', Auth::id())
            ->update(['is_deleted' => true, 'deleted_at' => now()]);
    }

    public function hideMessage(int $messageId): void
    {
        HiddenMessage::firstOrCreate([
            'user_id'    => Auth::id(),
            'message_id' => $messageId,
        ]);
    }

    public function unhideMessage(int $messageId): void
    {
        HiddenMessage::where('user_id', Auth::id())
            ->where('message_id', $messageId)
            ->delete();
    }

    public function startForward(int $messageId): void
    {
        $this->forwardingMessageId = $messageId;
        $this->showForwardModal    = true;
    }

    public function forwardMessage(): void
    {
        if (!$this->forwardingMessageId || !$this->forwardToUserId) return;

        $message   = Message::findOrFail($this->forwardingMessageId);
        $recipient = \App\Models\User::findOrFail($this->forwardToUserId);

        $result = app(MessageService::class)->forwardMessage($message, Auth::user(), $recipient);

        $this->showForwardModal    = false;
        $this->forwardingMessageId = null;
        $this->forwardToUserId     = 0;

        session()->flash('message', $result['success'] ? 'Message forwarded.' : $result['message']);
    }

    public function with(): array
    {
        $userId = Auth::id();

        $conversation = Conversation::with([
            'participants.user',
            'messages' => fn($q) => $q
                ->with(['sender', 'media', 'forwardedFrom.sender', 'hiddenBy'])
                ->orderBy('created_at'),
        ])->findOrFail($this->conversationId);

        $other = $conversation->getOtherParticipant($userId);

        return [
            'conversation' => $conversation,
            'messages'     => $conversation->messages,
            'other'        => $other,
            'userId'       => $userId,
        ];
    }

}; ?>

<div class="flex flex-col h-full">

    {{-- Header --}}
    <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden flex items-center justify-center flex-shrink-0">
                @if($other?->avatar_path)
                    <img src="{{ Storage::url($other->avatar_path) }}" class="w-full h-full object-cover" />
                @else
                    <span class="text-sm font-medium text-zinc-500">
                        {{ strtoupper(substr($other?->name ?? '?', 0, 2)) }}
                    </span>
                @endif
            </div>
            <a href="{{ route('profile.show', $other) }}"
               class="text-sm font-medium text-zinc-900 dark:text-white hover:underline">
                {{ $other?->name }}
            </a>
        </div>
    </div>

    {{-- Messages --}}
    <div
        class="flex-1 overflow-y-auto p-4 space-y-4"
        x-data
        x-on:scroll-to-bottom.window="$el.scrollTop = $el.scrollHeight"
        x-init="$el.scrollTop = $el.scrollHeight"
    >
        @foreach($messages as $message)
            @php $isMine  = $message->sender_id === $userId; @endphp
            @php $isHidden = $message->isHiddenBy($userId); @endphp

            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} gap-2 group">

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

                <div class="max-w-xs lg:max-w-md space-y-1" x-data="{ open: false }">

                    @if($message->type === 'forwarded')
                        <p class="text-xs text-zinc-400 {{ $isMine ? 'text-right' : '' }}">↗ Forwarded</p>
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

                        {{-- Text bubble --}}
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
                                        wire:click="startForward({{ $message->id }})"
                                        x-on:click="open = false"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left"
                                    >
                                        <flux:icon.arrow-uturn-right class="size-3.5" />
                                        Forward
                                    </button>
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
                    x-on:click="
                        $wire.prepareDraft().then(() => {
                            const id = $wire.draftMessageId;
                            if (id && pond) {
                                pond.setModelId(id, 'message');
                                pond.browse();
                            }
                        })
                    "
                    class="w-9 h-9 flex items-center justify-center rounded-full text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <flux:icon.paper-clip class="size-5" />
                </button>
                <input
                    type="file"
                    class="hidden"
                    x-init="
                        pond = initMessageMediaPond($el, {
                            onComplete: (path) => $wire.notifyUploadComplete(path, 'media'),
                        })
                    "
                />
            </div>

            <flux:textarea
                wire:model="body"
                placeholder="Message..."
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

    {{-- Forward modal --}}
    @if($showForwardModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 w-full max-w-sm space-y-4 mx-4">
                <p class="text-sm font-medium text-zinc-900 dark:text-white">Forward message</p>
                <flux:field>
                    <flux:label>Recipient user ID</flux:label>
                    <flux:input type="number" wire:model="forwardToUserId" placeholder="User ID..." />
                </flux:field>
                <div class="flex gap-2">
                    <flux:button wire:click="forwardMessage" variant="primary" class="flex-1">Forward</flux:button>
                    <flux:button wire:click="$set('showForwardModal', false)" variant="ghost">Cancel</flux:button>
                </div>
            </div>
        </div>
    @endif

</div>