<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\MessageForwarded;
use App\Events\ChatRoomMessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\ChatRoomMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageService
{
    /**
     * Get or create a direct conversation between two users
     */
    public function getOrCreateConversation(User $sender, User $recipient): array
    {
        // Check if recipient can receive messages
        if (!$recipient->canReceiveMessageFrom($sender)) {
            return [
                'success' => false,
                'message' => 'This user is not accepting messages from you.',
            ];
        }

        // Find existing conversation
        $existing = Conversation::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $sender->id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $recipient->id))
            ->first();

        if ($existing) {
            return ['success' => true, 'conversation' => $existing];
        }

        // Create new conversation
        $conversation = DB::transaction(function () use ($sender, $recipient) {
            $conversation = Conversation::create([
                'type'       => 'direct',
                'created_by' => $sender->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $sender->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $recipient->id,
            ]);

            return $conversation;
        });

        return ['success' => true, 'conversation' => $conversation];
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(
        Conversation $conversation,
        User         $sender,
        string       $body,
        array        $attachmentPaths = [],
        ?int         $forwardedFromId = null
    ): array {
        // Verify sender is participant
        $isParticipant = $conversation->participants()
            ->where('user_id', $sender->id)
            ->exists();

        if (!$isParticipant) {
            return ['success' => false, 'message' => 'You are not part of this conversation.'];
        }

        $message = DB::transaction(function () use (
            $conversation,
            $sender,
            $body,
            $attachmentPaths,
            $forwardedFromId
        ) {
            $type = $forwardedFromId ? 'forwarded' : (!empty($attachmentPaths) ? 'media' : 'text');

            $message = Message::create([
                'conversation_id'  => $conversation->id,
                'sender_id'        => $sender->id,
                'body'             => $body,
                'type'             => $type,
                'forwarded_from_id' => $forwardedFromId,
            ]);

            // Attach media
foreach ($attachmentPaths as $attachment) {
    $message->attachments()->create([
        'user_id'       => $attachment['user_id'] ?? $message->sender_id,
        'disk'          => $attachment['disk'] ?? 'public',
        'path'          => $attachment['path'],
        'filename'      => $attachment['filename'],
        'original_name' => $attachment['original_name'],
        'mime_type'     => $attachment['mime_type'],
        'size'          => $attachment['size'],
        'type'          => $attachment['type'],
        'thumbnail_url' => $attachment['thumbnail_url'] ?? null,
    ]);
Log::info('sendMessage called', [
    'body'            => $body,
    'attachmentPaths' => $attachmentPaths,
    'forwardedFromId' => $forwardedFromId,
]);
    
}

            // Update conversation timestamp
            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        // Load relationships for broadcast
        $message->load(['sender', 'attachments']);

        // Broadcast to conversation channel
        broadcast(new MessageSent($message, $conversation))->toOthers();

        return ['success' => true, 'message' => $message];
    }

    /**
     * Forward a message to another user
     */
    public function forwardMessage(
        Message $original,
        User    $sender,
        User    $recipient
    ): array {
        $result = $this->getOrCreateConversation($sender, $recipient);

        if (!$result['success']) {
            return $result;
        }

        $forwarded = $this->sendMessage(
            conversation:    $result['conversation'],
            sender:          $sender,
            body:            $original->body,
            forwardedFromId: $original->id
        );

        if ($forwarded['success']) {
            // Notify original sender
            broadcast(new MessageForwarded(
                message:     $original,
                recipientId: $original->sender_id,
            ))->toOthers();
        }

        return $forwarded;
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Send a message in a chat room
     */
    public function sendChatRoomMessage(
        ChatRoom $room,
        User     $sender,
        string   $body,
        string   $type = 'text'
    ): array {
        if (!$room->isMember($sender->id)) {
            return ['success' => false, 'message' => 'You are not a member of this room.'];
        }

        $message = ChatRoomMessage::create([
            'chat_room_id' => $room->id,
            'sender_id'    => $sender->id,
            'body'         => $body,
            'type'         => $type,
        ]);

        $room->update(['last_message_at' => now()]);

        $message->load('sender');

        broadcast(new ChatRoomMessageSent($message))->toOthers();

        // Update last read for sender
        $room->members()
            ->where('user_id', $sender->id)
            ->update(['last_read_at' => now()]);

        return ['success' => true, 'message' => $message];
    }

    /**
     * Create a chat room
     */
    public function createChatRoom(
        User    $owner,
        string  $name,
        string  $context = 'general',
        bool    $isPrivate = false,
        ?string $description = null
    ): array {
        if (!$owner->canCreateChatRoom()) {
            return [
                'success' => false,
                'message' => 'You have reached your chat room limit. Upgrade your plan for more.',
            ];
        }

        $room = DB::transaction(function () use ($owner, $name, $context, $isPrivate, $description) {
            $room = ChatRoom::create([
                'owner_id'    => $owner->id,
                'name'        => $name,
                'description' => $description,
                'context'     => $context,
                'is_private'  => $isPrivate,
            ]);

            ChatRoomMember::create([
                'chat_room_id' => $room->id,
                'user_id'      => $owner->id,
                'role'         => 'owner',
                'joined_at'    => now(),
            ]);

            return $room;
        });

        return ['success' => true, 'room' => $room];
    }

    /**
     * Add member to chat room
     */
    public function addMember(ChatRoom $room, User $user): array
    {
        if ($room->isMember($user->id)) {
            return ['success' => false, 'message' => 'Already a member.'];
        }

        ChatRoomMember::create([
            'chat_room_id' => $room->id,
            'user_id'      => $user->id,
            'role'         => 'member',
            'joined_at'    => now(),
        ]);

        return ['success' => true];
    }
}