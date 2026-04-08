<?php

namespace App\Http\Requests\MemoContext;

use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|min:1|max:120',
            'scope'             => 'required|string|in:global,group',
            'group_id'          => 'sometimes|nullable|integer|exists:groups,id',
            'media_type_filter' => 'sometimes|nullable|string|in:text,url,image,audio,video,document',
        ];
    }
}
