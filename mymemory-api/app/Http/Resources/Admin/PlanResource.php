<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'type'                  => $this->type,
            'is_active'             => (bool) $this->is_active,
            'max_memos'             => $this->max_memos,
            'storage_gb'            => (float) $this->storage_gb,
            'api_credits_per_month' => $this->api_credits_per_month,
            'downloads_per_month'   => $this->downloads_per_month,
            'supports_audio_video'  => (bool) $this->supports_audio_video,
            'supports_chunking'     => (bool) $this->supports_chunking,
            'price_cents'           => (int) $this->price_cents,
            'user_count'            => $this->users_count ?? null,
            'group_count'           => $this->groups_count ?? null,
        ];
    }
}
