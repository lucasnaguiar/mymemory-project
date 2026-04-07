<?php

namespace App\Services\Me;

use App\Models\User;

class UserProfileService
{
    public function getProfile(User $user): User
    {
        return $user->loadMissing('subscriptionPlan');
    }
}
