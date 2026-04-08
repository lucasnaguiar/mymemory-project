<?php

namespace App\Http\Resources\MemoContext;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'field_type'  => $this->field_type,
            'is_required' => (bool) $this->is_required,
        ];
    }
}
