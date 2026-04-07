<?php

namespace App\DTOs\Memo;

final class CreateTextDTO
{
    /**
     * @param  string[]|null  $keywords
     */
    public function __construct(
        public readonly ?string $title,
        public readonly string $content,
        public readonly ?string $summary,
        public readonly ?array $keywords,
        public readonly string $aiLevel,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            content: $data['content'],
            summary: $data['summary'] ?? null,
            keywords: isset($data['keywords']) ? (array) $data['keywords'] : null,
            aiLevel: $data['ai_level'] ?? 'full',
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
