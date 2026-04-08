<?php

namespace App\Http\Resources\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'owner_user_id'        => $this->owner_user_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'plan'                 => $this->whenLoaded('subscriptionPlan', fn () =>
                new GroupPlanResource($this->subscriptionPlan)
            ),
            'created_at'           => $this->created_at->toIso8601String(),
        ];
    }
}
