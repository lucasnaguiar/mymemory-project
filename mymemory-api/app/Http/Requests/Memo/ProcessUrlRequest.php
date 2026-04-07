<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class ProcessUrlRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'url'      => 'required|url|max:2048',
            'ai_level' => 'sometimes|string|in:none,basic,full',
            'group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
