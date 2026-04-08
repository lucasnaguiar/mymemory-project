<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                 => 'required|string|min:2|max:120',
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
        ];
    }
}
