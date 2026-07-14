<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'avatar',
        'context',
        'contextable_type',
        'contextable_id',
        'is_private',
        'max_participants',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_private'      => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(ChatRoomMember::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatRoomMessage::class)->latest();
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatRoomMessage::class)->latestOfMany();
    }

    public function contextable()
    {
        return $this->morphTo();
    }

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function unreadCount(int $userId): int
    {
        $member = $this->members()->where('user_id', $userId)->first();
        if (!$member) return 0;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('created_at', '>', $member->last_read_at ?? now()->subYears(10))
            ->count();
    }
}