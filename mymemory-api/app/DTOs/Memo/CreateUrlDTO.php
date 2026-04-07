<?php

namespace App\DTOs\Memo;

final class CreateUrlDTO
{
    /**
     * @param  string[]|null  $keywords
     */
    public function __construct(
        public readonly string $url,
        public readonly ?string $title,
        public readonly ?string $summary,
        public readonly ?array $keywords,
        public readonly string $aiLevel,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'],
            title: $data['title'] ?? null,
            summary: $data['summary'] ?? null,
            keywords: isset($data['keywords']) ? (array) $data['keywords'] : null,
            aiLevel: $data['ai_level'] ?? 'full',
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
