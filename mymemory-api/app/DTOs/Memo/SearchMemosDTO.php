<?php

namespace App\DTOs\Memo;

final class SearchMemosDTO
{
    public function __construct(
        public readonly string $query,
        public readonly string $operator,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly ?int $authorId,
        public readonly ?int $groupId,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'],
            operator: strtoupper($data['operator'] ?? 'AND'),
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            authorId: isset($data['author_id']) ? (int) $data['author_id'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            page: (int) ($data['page'] ?? 1),
            perPage: min((int) ($data['per_page'] ?? 20), 50),
        );
    }
}
