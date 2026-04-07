<?php

namespace App\Http\Resources\Me;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ai_level_text'             => $this->ai_level_text,
            'ai_level_url'              => $this->ai_level_url,
            'ai_level_image'            => $this->ai_level_image,
            'ai_level_audio'            => $this->ai_level_audio,
            'ai_level_video'            => $this->ai_level_video,
            'ai_level_document'         => $this->ai_level_document,
            'confirm_before_processing' => $this->confirm_before_processing,
            'sound_enabled'             => $this->sound_enabled,
            'ocr_correction_threshold'  => $this->ocr_correction_threshold,
            'updated_at'                => $this->updated_at->toIso8601String(),
        ];
    }
}
