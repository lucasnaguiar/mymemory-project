<?php

namespace App\Http\Resources\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Composite resource for the owner panel.
 * $this->resource is a plain array with keys: group, members, invites, memo_count.
 */
class GroupOwnerPanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $group   = $this->resource['group'];
        $members = $this->resource['members'];
        $invites = $this->resource['invites'];

        return [
            'group'       => new GroupResource($group),
            'memo_count'  => (int) $this->resource['memo_count'],
            'members'     => array_map(fn ($m) => [
                'id'        => $m->id,
                'user_id'   => $m->user_id,
                'name'      => $m->user->name ?? null,
                'email'     => $m->user->email ?? null,
                'role'      => $m->role,
                'joined_at' => $m->joined_at?->toIso8601String(),
            ], $members),
            'invites'     => GroupInviteResource::collection($invites),
        ];
    }
}
