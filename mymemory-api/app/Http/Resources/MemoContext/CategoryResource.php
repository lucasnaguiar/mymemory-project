<?php

namespace App\Http\Resources\MemoContext;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'scope'             => $this->scope,
            'group_id'          => $this->group_id,
            'media_type_filter' => $this->media_type_filter,
            'subcategories'     => SubcategoryResource::collection($this->whenLoaded('subcategories')),
            'fields'            => FieldResource::collection($this->whenLoaded('fields')),
        ];
    }
}
