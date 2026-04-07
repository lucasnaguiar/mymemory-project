<?php

namespace App\DTOs\Memo;

final class ProcessUrlDTO
{
    public function __construct(
        public readonly string $url,
        public readonly string $aiLevel,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            aiLevel: $data['ai_level'] ?? 'full',
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
