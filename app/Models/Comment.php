<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'commentable_type', 'commentable_id',
        'body', 'is_flagged', 'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function responses()
    {
        return $this->hasMany(Response::class)->latest();
    }

    public function votes()
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function upvotes(): int
    {
        return $this->votes()->where('type', 'up')->count();
    }

    public function downvotes(): int
    {
        return $this->votes()->where('type', 'down')->count();
    }
}