<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiddenChatRoomMessage extends Model
{
    protected $fillable = ['user_id', 'chat_room_message_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chatRoomMessage()
    {
        return $this->belongsTo(ChatRoomMessage::class);
    }
}