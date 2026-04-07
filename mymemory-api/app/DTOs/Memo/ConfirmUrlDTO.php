<?php

namespace App\DTOs\Memo;

final class ConfirmUrlDTO
{
    /**
     * @param  string[]|null  $keywords
     */
    public function __construct(
        public readonly int $memoId,
        public readonly ?string $title,
        public readonly ?string $summary,
        public readonly ?array $keywords,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            memoId: (int) $data['memo_id'],
            title: $data['title'] ?? null,
            summary: $data['summary'] ?? null,
            keywords: isset($data['keywords']) ? (array) $data['keywords'] : null,
        );
    }
}
