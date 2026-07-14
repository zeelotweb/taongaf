<?php

use App\Models\Conversation;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

// Private conversation channel
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()
        ->where('conversations.id', $conversationId)
        ->exists();
});

// Private user channel — for notifications and forwarded messages
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Presence channel for chat rooms
Broadcast::channel('chat-room.{roomId}', function ($user, $roomId) {
    $room = ChatRoom::find($roomId);
    if (!$room || !$room->isMember($user->id)) return false;

    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->avatar_path,
    ];
});

// Global online presence channel
Broadcast::channel('online', function ($user) {
    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->avatar_path,
    ];
});