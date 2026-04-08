<?php

namespace App\Services\Memo;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\SearchMemosDTO;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class MemoQueryService
{
    public function __construct(private readonly AiProviderInterface $ai) {}

    /**
     * Return the most recent confirmed memos accessible by the user.
     *
     * @return Memo[]
     */
    public function recent(User $user, int $limit = 12, ?int $groupId = null): array
    {
        return Memo::query()
            ->confirmed()
            ->accessibleBy($user, $groupId)
            ->with(['user:id,name,email', 'file'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Full-text search with AND/OR logic, date/author filters, and highlighted terms.
     *
     * @return array{items: Memo[], total: int, highlight_terms: string[]}
     */
    public function search(User $user, SearchMemosDTO $dto): array
    {
        $terms = array_values(array_filter(
            preg_split('/\s+/', trim($dto->query), -1, PREG_SPLIT_NO_EMPTY) ?? []
        ));

        $query = Memo::query()
            ->confirmed()
            ->accessibleBy($user, $dto->groupId)
            ->with(['user:id,name,email', 'file'])
            ->orderByDesc('created_at');

        // Text filters
        if (!empty($terms)) {
            if ($dto->operator === 'OR') {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere(fn ($inner) => $this->applyTermFilter($inner, $term));
                    }
                });
            } else {
                foreach ($terms as $term) {
                    $query->where(fn ($q) => $this->applyTermFilter($q, $term));
                }
            }
        }

        if ($dto->dateFrom) {
            $query->where('created_at', '>=', $dto->dateFrom);
        }
        if ($dto->dateTo) {
            $query->where('created_at', '<=', $dto->dateTo . ' 23:59:59');
        }
        if ($dto->authorId) {
            $query->where('user_id', $dto->authorId);
        }

        /** @var LengthAwarePaginator $paginated */
        $paginated = $query->paginate($dto->perPage, ['*'], 'page', $dto->page);

        return [
            'items'           => $paginated->items(),
            'total'           => $paginated->total(),
            'highlight_terms' => $terms,
        ];
    }

    /**
     * Generate synonym expansions for a search query via AI.
     *
     * @return string[]
     */
    public function synonyms(string $query): array
    {
        return $this->ai->generateSearchSynonyms($query);
    }

    /**
     * Return distinct authors of memos accessible by the user.
     *
     * @return array<int, array{id: int, name: string|null, email: string}>
     */
    public function authors(User $user, ?int $groupId = null): array
    {
        $memoQuery = Memo::query()->confirmed()->accessibleBy($user, $groupId)->select('user_id');

        return \App\Models\User::query()
            ->whereIn('id', $memoQuery)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    // -------------------------------------------------------------------------

    private function applyTermFilter(\Illuminate\Database\Eloquent\Builder $query, string $term): void
    {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], mb_strtolower($term)) . '%';

        $query->where(function ($q) use ($like) {
            $q->whereRaw('LOWER(COALESCE(title, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(summary, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(content, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(source_url, \'\')) LIKE ?', [$like]);
        });
    }
}
