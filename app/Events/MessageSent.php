<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message      $message,
        public readonly Conversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id'          => $this->message->id,
                'body'        => $this->message->body,
                'type'        => $this->message->type,
                'sender_id'   => $this->message->sender_id,
                'sender_name' => $this->message->sender->name,
                'sender_avatar' => $this->message->sender->avatar_path,
                'created_at'  => $this->message->created_at->toISOString(),
                'attachments' => $this->message->attachments->map(fn($a) => [
                    'id'            => $a->id,
                    'type'          => $a->type,
                    'path'          => $a->path,
                    'original_name' => $a->original_name,
                    'thumbnail_url' => $a->thumbnail_url,
                ]),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}