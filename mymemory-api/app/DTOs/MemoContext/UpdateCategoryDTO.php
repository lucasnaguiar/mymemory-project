<?php

namespace App\DTOs\MemoContext;

final class UpdateCategoryDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $mediaTypeFilter,
        public readonly bool    $clearMediaTypeFilter,
    ) {}

    public static function fromArray(array $data): self
    {
        $hasFilter = array_key_exists('media_type_filter', $data);
        return new self(
            name:                 isset($data['name']) ? trim($data['name']) : null,
            mediaTypeFilter:      ($hasFilter && $data['media_type_filter'] !== null)
                                      ? $data['media_type_filter']
                                      : null,
            clearMediaTypeFilter: $hasFilter && $data['media_type_filter'] === null,
        );
    }
}
