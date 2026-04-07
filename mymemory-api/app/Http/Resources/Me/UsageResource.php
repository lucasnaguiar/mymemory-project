<?php

namespace App\Http\Resources\Me;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{
 *   plan_name: string,
 *   memos: array{used: int, limit: int|null},
 *   storage_bytes: array{used: int, limit_gb: float},
 *   api_credits: array{used: int, limit: int|null},
 *   downloads: array{used: int, limit: int|null},
 * } $resource
 */
class UsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
