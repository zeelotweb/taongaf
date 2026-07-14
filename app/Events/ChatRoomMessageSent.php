<?php

namespace App\Events;

use App\Models\ChatRoomMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatRoomMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatRoomMessage $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat-room.' . $this->message->chat_room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id'            => $this->message->id,
                'body'          => $this->message->body,
                'type'          => $this->message->type,
                'sender_id'     => $this->message->sender_id,
                'sender_name'   => $this->message->sender->name,
                'sender_avatar' => $this->message->sender->avatar_path,
                'created_at'    => $this->message->created_at->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }
}