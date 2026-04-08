<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class ProcessImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'     => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:204800',
            'ai_level' => 'sometimes|string|in:none,basic,full',
            'group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
