<?php

namespace App\Http\Resources\Memo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'status'     => $this->status,
            'title'      => $this->title,
            'content'    => $this->content,
            'summary'    => $this->summary,
            'keywords'   => $this->keywords ?? [],
            'source_url' => $this->source_url,
            'ai_level'   => $this->ai_level,
            'group_id'   => $this->group_id,
            'user_id'    => $this->user_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
