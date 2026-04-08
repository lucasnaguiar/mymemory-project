<?php

namespace App\Http\Resources\Group;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'price_cents'           => $this->price_cents,
            'storage_gb'            => (float) $this->storage_gb,
            'max_memos'             => $this->max_memos,
            'api_credits_per_month' => $this->api_credits_per_month,
            'downloads_per_month'   => $this->downloads_per_month,
            'supports_audio_video'  => (bool) $this->supports_audio_video,
            'supports_chunking'     => (bool) $this->supports_chunking,
        ];
    }
}
