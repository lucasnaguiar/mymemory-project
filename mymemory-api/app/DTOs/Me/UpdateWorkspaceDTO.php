<?php

namespace App\DTOs\Me;

final class UpdateWorkspaceDTO
{
    public function __construct(
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            groupId: isset($data['group_id']) && $data['group_id'] !== null
                ? (int) $data['group_id']
                : null,
        );
    }
}
