<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmUrlRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'memo_id'    => 'required|integer|exists:memos,id',
            'title'      => 'sometimes|nullable|string|max:255',
            'summary'    => 'sometimes|nullable|string|max:2000',
            'keywords'   => 'sometimes|nullable|array',
            'keywords.*' => 'string|max:100',
        ];
    }
}
