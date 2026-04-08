<?php

namespace App\Http\Requests\MemoContext;

use Illuminate\Foundation\Http\FormRequest;

class CreateFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|min:1|max:120',
            'field_type'  => 'sometimes|string|in:text,number,date,boolean',
            'is_required' => 'sometimes|boolean',
        ];
    }
}
