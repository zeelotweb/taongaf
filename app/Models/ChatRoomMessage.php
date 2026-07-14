<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoomMessage extends Model
{
    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'body',
        'type',
        'is_deleted',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function hiddenBy()
{
    return $this->hasMany(HiddenChatRoomMessage::class);
}

public function isHiddenBy(int $userId): bool
{
    return $this->hiddenBy()->where('user_id', $userId)->exists();
}
public function media()
{
    return $this->morphMany(\App\Models\Media::class, 'mediable')->latest();
}
}