<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferencePolicy
{
    /**
     * A user may only view/update their own preferences.
     */
    public function update(User $user, UserPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }

    public function view(User $user, UserPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }
}
