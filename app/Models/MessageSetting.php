<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageSetting extends Model
{
    protected $fillable = [
        'user_id',
        'who_can_message',
        'allowed_user_ids',
        'blocked_user_ids',
    ];

    protected function casts(): array
    {
        return [
            'allowed_user_ids' => 'array',
            'blocked_user_ids' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function canReceiveFrom(User $sender): bool
    {
        // Blocked users can never message
        if (in_array($sender->id, $this->blocked_user_ids ?? [])) {
            return false;
        }

        return match($this->who_can_message) {
            'anyone'            => true,
            'selected_users'    => in_array($sender->id, $this->allowed_user_ids ?? []),
            'community_members' => $this->user->communityMembers()
                ->where('user_id', $sender->id)
                ->where('status', 'active')
                ->exists(),
            'staff_only'        => $sender->isStaffOf($this->user),
            default             => true,
        };
    }
}