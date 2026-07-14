<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformCredit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'creditable_type',
        'creditable_id',
        'type',
        'is_used',
        'used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used'    => 'boolean',
            'used_at'    => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creditable()
    {
        return $this->morphTo();
    }

    public function isValid(): bool
    {
        return !$this->is_used
            && (!$this->expires_at || $this->expires_at->isFuture());
    }
}