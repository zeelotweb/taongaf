<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileCommerceSetting extends Model
{
    protected $fillable = [
        'user_id',
        'is_enabled',
        'allowed_services',
        'promotion_fee',
        'auto_approve',
        'is_unlocked',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'       => 'boolean',
            'allowed_services' => 'array',
            'auto_approve'     => 'boolean',
            'is_unlocked'      => 'boolean',
            'unlocked_at'      => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allowsService(string $service): bool
    {
        return in_array($service, $this->allowed_services ?? []);
    }
}