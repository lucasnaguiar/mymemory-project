<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class SearchMemosRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'query'     => 'required|string|min:1|max:500',
            'operator'  => 'sometimes|string|in:AND,OR',
            'date_from' => 'sometimes|nullable|date',
            'date_to'   => 'sometimes|nullable|date',
            'author_id' => 'sometimes|nullable|integer|exists:users,id',
            'group_id'  => 'sometimes|nullable|integer|exists:groups,id',
            'page'      => 'sometimes|integer|min:1',
            'per_page'  => 'sometimes|integer|min:1|max:50',
        ];
    }
}
