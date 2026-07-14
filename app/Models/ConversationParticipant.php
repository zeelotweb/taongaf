<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
        'is_muted',
        'is_archived',
        'is_blocked',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'deleted_at'   => 'datetime',
            'is_muted'     => 'boolean',
            'is_archived'  => 'boolean',
            'is_blocked'   => 'boolean',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}