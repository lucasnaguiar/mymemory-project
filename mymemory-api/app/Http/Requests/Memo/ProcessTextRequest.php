<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTextRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'content'  => 'required|string|min:1|max:50000',
            'ai_level' => 'sometimes|string|in:none,basic,full',
            'group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
