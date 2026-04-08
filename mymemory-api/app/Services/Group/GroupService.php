<?php

namespace App\Services\Group;

use App\DTOs\Group\AcceptInviteDTO;
use App\DTOs\Group\CreateGroupDTO;
use App\DTOs\Group\CreateInviteDTO;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\GroupMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Str;

class GroupService
{
    private const INVITE_EXPIRE_DAYS = 7;

    /**
     * Create a new group owned by the user.
     *
     * @throws BusinessRuleException
     */
    public function createGroup(User $user, CreateGroupDTO $dto): Group
    {
        $plan = SubscriptionPlan::find($dto->subscriptionPlanId);

        if (!$plan || !$plan->is_active) {
            throw new BusinessRuleException('Plano de grupo inativo ou inválido.');
        }

        if ($plan->type !== 'group') {
            throw new BusinessRuleException('O plano selecionado não é um plano de grupo.');
        }

        $group = Group::create([
            'name'                 => $dto->name,
            'owner_user_id'        => $user->id,
            'subscription_plan_id' => $dto->subscriptionPlanId,
        ]);

        return $group->load('subscriptionPlan');
    }

    /**
     * Aggregate owner panel data for a group.
     *
     * @return array{group: Group, members: GroupMember[], invites: GroupInvite[], memo_count: int}
     */
    public function ownerPanel(Group $group): array
    {
        $group->loadMissing('subscriptionPlan');

        $members = GroupMember::with('user:id,name,email')
            ->where('group_id', $group->id)
            ->orderBy('joined_at')
            ->get()
            ->all();

        $invites = GroupInvite::where('group_id', $group->id)
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $memoCount = $group->memos()->confirmed()->count();

        return [
            'group'      => $group,
            'members'    => $members,
            'invites'    => $invites,
            'memo_count' => $memoCount,
        ];
    }

    /**
     * Create an invite link for the given email+role in the group.
     *
     * @throws BusinessRuleException
     */
    public function createInvite(User $inviter, Group $group, CreateInviteDTO $dto): GroupInvite
    {
        // Check for a pending invite already
        $existing = GroupInvite::where('group_id', $group->id)
            ->where('email', $dto->email)
            ->whereNull('accepted_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($existing) {
            throw new BusinessRuleException('Já existe um convite pendente para este e-mail neste grupo.');
        }

        // Also check if already a member
        $alreadyMember = GroupMember::where('group_id', $group->id)
            ->whereHas('user', fn ($q) => $q->where('email', $dto->email))
            ->exists();

        if ($alreadyMember) {
            throw new BusinessRuleException('Este usuário já é membro do grupo.');
        }

        return GroupInvite::create([
            'group_id'           => $group->id,
            'email'              => $dto->email,
            'role'               => $dto->role,
            'token'              => Str::random(64),
            'invited_by_user_id' => $inviter->id,
            'expires_at'         => now()->addDays(self::INVITE_EXPIRE_DAYS),
        ]);
    }

    /**
     * Accept a group invite by token, adding the user as a member.
     *
     * @throws BusinessRuleException
     * @throws ResourceNotFoundException
     */
    public function acceptInvite(User $user, AcceptInviteDTO $dto): Group
    {
        /** @var GroupInvite|null $invite */
        $invite = GroupInvite::withoutGlobalScopes()->where('token', $dto->token)->first();

        if (!$invite) {
            throw new ResourceNotFoundException('Convite');
        }

        if ($invite->accepted_at !== null) {
            throw new BusinessRuleException('Este convite já foi aceito.');
        }

        if ($invite->expires_at !== null && $invite->expires_at->isPast()) {
            throw new BusinessRuleException('Este convite expirou.');
        }

        if (strtolower($user->email) !== strtolower($invite->email)) {
            throw new BusinessRuleException('Este convite pertence a outro e-mail.');
        }

        $alreadyMember = GroupMember::where('group_id', $invite->group_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyMember) {
            throw new BusinessRuleException('Você já é membro deste grupo.');
        }

        GroupMember::create([
            'group_id'  => $invite->group_id,
            'user_id'   => $user->id,
            'role'      => $invite->role,
            'joined_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        return Group::findOrFail($invite->group_id);
    }
}
