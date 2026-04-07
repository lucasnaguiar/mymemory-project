<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class CreateTextRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'      => 'sometimes|nullable|string|max:255',
            'content'    => 'required|string|min:1|max:50000',
            'summary'    => 'sometimes|nullable|string|max:2000',
            'keywords'   => 'sometimes|nullable|array',
            'keywords.*' => 'string|max:100',
            'ai_level'   => 'sometimes|string|in:none,basic,full',
            'group_id'   => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
