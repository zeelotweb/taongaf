<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioMembership extends Model
{
    protected $fillable = [
        'publisher_id',
        'user_id',
        'roles',
        'status',
        'invite_token',
        'invited_at',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'roles'      => 'array',
            'invited_at' => 'datetime',
            'joined_at'  => 'datetime',
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

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}