<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityMember extends Model
{
    protected $fillable = [
        'publisher_id',
        'user_id',
        'type',
        'token_price',
        'subscribed_at',
        'subscription_ends_at',
        'auto_renew',
        'status',
        'blocked_at',
        'block_reason',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at'        => 'datetime',
            'subscription_ends_at' => 'datetime',
            'blocked_at'           => 'datetime',
            'auto_renew'           => 'boolean',
        ];
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isSubscriptionValid(): bool
    {
        return $this->type === 'subscribed'
            && $this->status === 'active'
            && $this->subscription_ends_at
            && $this->subscription_ends_at->isFuture();
    }
}