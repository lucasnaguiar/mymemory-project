<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class UploadMemoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'     => 'required|file|max:1048576',
            'ai_level' => 'sometimes|string|in:none,basic,full',
            'group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
