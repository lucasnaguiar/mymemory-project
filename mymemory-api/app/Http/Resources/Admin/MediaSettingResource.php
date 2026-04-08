<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                               => $this->id,
            'subscription_plan_id'             => $this->subscription_plan_id,
            'media_type'                       => $this->media_type,
            'max_file_size_mb'                 => (int) $this->max_file_size_mb,
            'max_chunk_minutes'                => $this->max_chunk_minutes,
            'ocr_correction_threshold_default' => $this->ocr_correction_threshold_default,
        ];
    }
}
