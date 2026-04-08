<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class AcceptInviteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'token' => 'required|string|size:64',
        ];
    }
}
