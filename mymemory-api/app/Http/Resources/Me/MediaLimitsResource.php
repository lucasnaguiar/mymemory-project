<?php

namespace App\Http\Resources\Me;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaLimitsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
