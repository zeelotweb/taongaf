<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'type',
        'forwarded_from_id',
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

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function forwardedFrom()
    {
        return $this->belongsTo(Message::class, 'forwarded_from_id');
    }
    public function hiddenBy()
{
    return $this->hasMany(HiddenMessage::class);
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