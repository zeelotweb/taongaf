<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'plan',
        'price_usd',
        'status',
        'trial_ends_at',
        'current_period_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at'           => 'datetime',
            'current_period_ends_at'  => 'datetime',
            'cancelled_at'            => 'datetime',
            'price_usd'               => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }
}