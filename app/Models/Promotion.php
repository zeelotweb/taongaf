<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'hustler_id',
        'profile_owner_id',
        'promotable_type',
        'promotable_id',
        'service_type',
        'tokens_paid',
        'total_sales_tokens',
        'hustler_commission_earned',
        'profile_owner_earned',
        'platform_earned',
        'referral_token',
        'status',
        'approved_at',
        'rejected_at',
        'starts_at',
        'ends_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'starts_at'   => 'datetime',
            'ends_at'     => 'datetime',
        ];
    }

    public function hustler()
    {
        return $this->belongsTo(User::class, 'hustler_id');
    }

    public function profileOwner()
    {
        return $this->belongsTo(User::class, 'profile_owner_id');
    }

    public function promotable()
    {
        return $this->morphTo();
    }

    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && (!$this->starts_at || $this->starts_at->isPast())
            && (!$this->ends_at || $this->ends_at->isFuture());
    }
}