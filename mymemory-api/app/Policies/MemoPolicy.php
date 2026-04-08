<?php

namespace App\Policies;

use App\Models\Memo;
use App\Models\User;

class MemoPolicy
{
    /**
     * View: owner or any group member can view.
     */
    public function view(User $user, Memo $memo): bool
    {
        if ($memo->user_id === $user->id) {
            return true;
        }

        if ($memo->group_id !== null) {
            return $user->groupMemberships()->where('group_id', $memo->group_id)->exists()
                || $user->ownedGroups()->where('id', $memo->group_id)->exists();
        }

        return false;
    }

    /**
     * Update / delete: only the memo owner.
     */
    public function update(User $user, Memo $memo): bool
    {
        return $memo->user_id === $user->id;
    }

    public function delete(User $user, Memo $memo): bool
    {
        return $memo->user_id === $user->id;
    }

    public function restore(User $user, Memo $memo): bool
    {
        return $memo->user_id === $user->id;
    }
}
