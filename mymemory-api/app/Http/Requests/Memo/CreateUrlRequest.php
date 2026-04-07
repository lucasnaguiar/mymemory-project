<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class CreateUrlRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'url'        => 'required|url|max:2048',
            'title'      => 'sometimes|nullable|string|max:255',
            'summary'    => 'sometimes|nullable|string|max:2000',
            'keywords'   => 'sometimes|nullable|array',
            'keywords.*' => 'string|max:100',
            'ai_level'   => 'sometimes|string|in:none,basic,full',
            'group_id'   => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
