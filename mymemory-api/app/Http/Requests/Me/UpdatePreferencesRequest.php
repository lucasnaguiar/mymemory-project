<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $aiLevels = 'sometimes|string|in:none,basic,full';

        return [
            'ai_level_text'             => $aiLevels,
            'ai_level_url'              => $aiLevels,
            'ai_level_image'            => $aiLevels,
            'ai_level_audio'            => $aiLevels,
            'ai_level_video'            => $aiLevels,
            'ai_level_document'         => $aiLevels,
            'confirm_before_processing' => 'sometimes|boolean',
            'sound_enabled'             => 'sometimes|boolean',
            'ocr_correction_threshold'  => 'sometimes|nullable|integer|min:1|max:100',
        ];
    }
}
