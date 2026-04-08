<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

class ProcessAudioRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file'     => 'required|file|mimes:mp3,wav,ogg,oga,opus,webm,weba,m4a,flac,aac|max:512000',
            'ai_level' => 'sometimes|string|in:none,basic,full',
            'group_id' => 'sometimes|nullable|integer|exists:groups,id',
        ];
    }
}
