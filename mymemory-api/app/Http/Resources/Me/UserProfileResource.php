<?php

namespace App\Http\Resources\Me;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'name'                      => $this->name,
            'email'                     => $this->email,
            'email_verified_at'         => $this->email_verified_at?->toIso8601String(),
            'role'                      => $this->role,
            'active_workspace_group_id' => $this->active_workspace_group_id,
            'subscription_plan'         => $this->whenLoaded('subscriptionPlan', fn () => [
                'id'                   => $this->subscriptionPlan->id,
                'name'                 => $this->subscriptionPlan->name,
                'type'                 => $this->subscriptionPlan->type,
                'storage_gb'           => $this->subscriptionPlan->storage_gb,
                'supports_audio_video' => $this->subscriptionPlan->supports_audio_video,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
