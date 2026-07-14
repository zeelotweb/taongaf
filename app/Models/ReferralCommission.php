<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    protected $fillable = [
        'promotion_id',
        'purchase_id',
        'buyer_id',
        'hustler_id',
        'profile_owner_id',
        'publisher_id',
        'sale_amount',
        'publisher_earned',
        'hustler_earned',
        'profile_owner_earned',
        'platform_earned',
        'publisher_is_hustler',
        'attributed_via',
        'commission_audited',
        'overage_credit',
        'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'publisher_is_hustler' => 'boolean',
            'commission_audited'   => 'boolean',
            'audited_at'           => 'datetime',
        ];
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function hustler()
    {
        return $this->belongsTo(User::class, 'hustler_id');
    }

    public function profileOwner()
    {
        return $this->belongsTo(User::class, 'profile_owner_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }
}