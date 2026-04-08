<?php

namespace App\DTOs\MemoContext;

final class CreateFieldDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $fieldType,
        public readonly bool   $isRequired,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:       trim($data['name']),
            fieldType:  $data['field_type'] ?? 'text',
            isRequired: (bool) ($data['is_required'] ?? false),
        );
    }
}
