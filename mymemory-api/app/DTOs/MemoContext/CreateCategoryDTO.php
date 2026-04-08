<?php

namespace App\DTOs\MemoContext;

final class CreateCategoryDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $scope,
        public readonly ?int    $groupId,
        public readonly ?string $mediaTypeFilter,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:            trim($data['name']),
            scope:           $data['scope'] ?? 'global',
            groupId:         isset($data['group_id']) ? (int) $data['group_id'] : null,
            mediaTypeFilter: $data['media_type_filter'] ?? null,
        );
    }
}
