<?php

namespace App\Services\Memo;

use App\Exceptions\BusinessRuleException;
use App\Models\User;

class PlanLimitService
{
    /**
     * Assert the user can create a new memo given their active plan.
     *
     * @throws BusinessRuleException
     */
    public function assertCanCreateMemo(User $user): void
    {
        $user->loadMissing('subscriptionPlan', 'activeWorkspaceGroup.subscriptionPlan');

        $plan = $user->active_workspace_group_id !== null
            ? $user->activeWorkspaceGroup?->subscriptionPlan
            : $user->subscriptionPlan;

        if ($plan === null || $plan->max_memos === null) {
            return; // No limit or no plan configured
        }

        $count = $user->memos()
            ->where('status', 'confirmed')
            ->when($user->active_workspace_group_id, fn ($q) => $q->where('group_id', $user->active_workspace_group_id))
            ->whereNull('deleted_at')
            ->count();

        if ($count >= $plan->max_memos) {
            throw new BusinessRuleException(
                "You have reached the memo limit ({$plan->max_memos}) for your plan.",
                'memo_limit_reached',
            );
        }
    }
}
