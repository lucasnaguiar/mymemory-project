<?php

namespace App\Http\Resources\Me;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'role'       => $this->pivot?->role ?? 'editor',
            'is_owner'   => $this->owner_user_id === $request->user()?->id,
            'plan'       => $this->whenLoaded('subscriptionPlan', fn () => [
                'id'   => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'type' => $this->subscriptionPlan->type,
            ]),
        ];
    }
}
