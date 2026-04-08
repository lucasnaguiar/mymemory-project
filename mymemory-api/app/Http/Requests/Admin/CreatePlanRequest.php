<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH');
        $req      = $isUpdate ? 'sometimes|' : '';

        return [
            'name'                   => $req . 'required|string|min:2|max:120',
            'type'                   => $req . 'required|string|in:individual,group',
            'is_active'              => 'sometimes|boolean',
            'max_memos'              => 'sometimes|nullable|integer|min:1',
            'storage_gb'             => $req . 'required|numeric|min:0.1',
            'api_credits_per_month'  => 'sometimes|nullable|numeric|min:0',
            'downloads_per_month'    => 'sometimes|nullable|integer|min:0',
            'supports_audio_video'   => 'sometimes|boolean',
            'supports_chunking'      => 'sometimes|boolean',
            'price_cents'            => 'sometimes|integer|min:0',
        ];
    }
}
