<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purchasable_type',
        'purchasable_id',
        'tokens_spent',
        'publisher_id',
        'publisher_earned',
        'platform_cut',
        'access_expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tokens_spent' => 'integer',
            'publisher_earned' => 'integer',
            'platform_cut' => 'integer',
            'access_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function purchasable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('access_expires_at', '<', now());
    }

    // Helpers
    public function isExpired(): bool
    {
        if (!$this->access_expires_at) return false;
        return $this->access_expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}