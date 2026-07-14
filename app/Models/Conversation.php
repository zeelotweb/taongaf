<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function getOtherParticipant(int $userId): ?User
    {
        return $this->participants()
            ->where('user_id', '!=', $userId)
            ->with('user')
            ->first()
            ?->user;
    }

    public function unreadCount(int $userId): int
    {
        $participant = $this->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant) return 0;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('created_at', '>', $participant->last_read_at ?? now()->subYears(10))
            ->count();
    }
}