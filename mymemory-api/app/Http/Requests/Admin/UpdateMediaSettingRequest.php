<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'media_type'                       => 'required|string|in:image,audio,video,document',
            'max_file_size_mb'                 => 'required|integer|min:1',
            'max_chunk_minutes'                => 'sometimes|nullable|integer|min:1|max:120',
            'ocr_correction_threshold_default' => 'sometimes|nullable|integer|min:1|max:100',
        ];
    }
}
