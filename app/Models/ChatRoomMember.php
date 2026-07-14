<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoomMember extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role',
        'is_muted',
        'last_read_at',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_muted'     => 'boolean',
            'last_read_at' => 'datetime',
            'joined_at'    => 'datetime',
        ];
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }
}