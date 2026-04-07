<?php

namespace App\DTOs\Memo;

final class ProcessTextDTO
{
    public function __construct(
        public readonly string $content,
        public readonly string $aiLevel,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            aiLevel: $data['ai_level'] ?? 'full',
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
