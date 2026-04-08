<?php

namespace App\DTOs\MemoContext;

final class CreateSubcategoryDTO
{
    public function __construct(public readonly string $name) {}

    public static function fromArray(array $data): self
    {
        return new self(name: trim($data['name']));
    }
}
