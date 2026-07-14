<?php

namespace App\Policies;

use App\Models\Editorial;
use App\Models\User;

class EditorialPolicy
{
    public function update(User $user, Editorial $editorial): bool
    {
        return $user->id === $editorial->user_id;
    }

    public function delete(User $user, Editorial $editorial): bool
    {
        return $user->id === $editorial->user_id;
    }
}