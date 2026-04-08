<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /** Owner or any member may view. */
    public function view(User $user, Group $group): bool
    {
        return $this->isOwner($user, $group) || $this->isMember($user, $group);
    }

    /** Only the owner may see the full owner panel. */
    public function ownerPanel(User $user, Group $group): bool
    {
        return $this->isOwner($user, $group);
    }

    /** Only the owner may send invites. */
    public function invite(User $user, Group $group): bool
    {
        return $this->isOwner($user, $group);
    }

    /** Only the owner may update or delete the group. */
    public function update(User $user, Group $group): bool
    {
        return $this->isOwner($user, $group);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->isOwner($user, $group);
    }

    // -------------------------------------------------------------------------

    private function isOwner(User $user, Group $group): bool
    {
        return $group->owner_user_id === $user->id;
    }

    private function isMember(User $user, Group $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }
}
