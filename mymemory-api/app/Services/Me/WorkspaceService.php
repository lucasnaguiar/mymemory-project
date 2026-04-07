<?php

namespace App\Services\Me;

use App\DTOs\Me\UpdateWorkspaceDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceService
{
    /**
     * Returns all groups the user belongs to (owner or member), with their plan.
     */
    public function getWorkspaceGroups(User $user): Collection
    {
        return Group::query()
            ->with('subscriptionPlan')
            ->where(function ($query) use ($user) {
                $query->where('owner_user_id', $user->id)
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
            })
            ->whereNull('deleted_at')
            ->get()
            ->each(function (Group $group) use ($user) {
                // Attach pivot role from group_members (null for owner)
                $member = $group->members->firstWhere('user_id', $user->id);
                $group->pivot = (object) ['role' => $member?->role ?? 'editor'];
            });
    }

    /**
     * Switches the user's active workspace to the given group (or personal if null).
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function updateWorkspace(User $user, UpdateWorkspaceDTO $dto): User
    {
        if ($dto->groupId !== null) {
            $group = Group::find($dto->groupId);

            if (!$group) {
                throw new ResourceNotFoundException('Group');
            }

            $isMember = $group->owner_user_id === $user->id
                || $group->members()->where('user_id', $user->id)->exists();

            if (!$isMember) {
                throw new AuthorizationException('You are not a member of this group.');
            }
        }

        $user->update(['active_workspace_group_id' => $dto->groupId]);

        return $user->fresh();
    }
}
