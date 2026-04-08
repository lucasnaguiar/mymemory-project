<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'      => 'sometimes|nullable|string|max:255',
            'summary'    => 'sometimes|nullable|string|max:2000',
            'keywords'   => 'sometimes|nullable|array',
            'keywords.*' => 'string|max:100',
            'content'    => 'sometimes|nullable|string|max:100000',
        ];
    }
}
