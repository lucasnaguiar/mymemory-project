<?php

namespace App\Http\Requests\MemoContext;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|min:1|max:120',
            'media_type_filter' => 'sometimes|nullable|string|in:text,url,image,audio,video,document',
        ];
    }
}
