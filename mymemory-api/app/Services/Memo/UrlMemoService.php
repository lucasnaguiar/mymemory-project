<?php

namespace App\Services\Memo;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\ConfirmUrlDTO;
use App\DTOs\Memo\CreateUrlDTO;
use App\DTOs\Memo\ProcessUrlDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Memo;
use App\Models\MonthlyUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UrlMemoService
{
    public function __construct(
        private readonly AiProviderInterface $ai,
        private readonly PlanLimitService $planLimit,
    ) {}

    /**
     * Process step: fetch URL, extract content, run AI, return draft memo.
     */
    public function process(User $user, ProcessUrlDTO $dto): Memo
    {
        $aiOutput = $this->ai->extractAndSummarizeUrl($dto->url, $dto->aiLevel);

        return DB::transaction(function () use ($user, $dto, $aiOutput) {
            // Remove stale drafts for this user/type
            Memo::where('user_id', $user->id)
                ->where('type', 'url')
                ->where('status', 'draft')
                ->where('group_id', $dto->groupId)
                ->delete();

            return Memo::create([
                'user_id'    => $user->id,
                'group_id'   => $dto->groupId,
                'type'       => 'url',
                'status'     => 'draft',
                'source_url' => $dto->url,
                'content'    => $aiOutput->extractedContent ?: null,
                'summary'    => $aiOutput->summary ?: null,
                'keywords'   => $aiOutput->keywords ?: null,
                'ai_level'   => $dto->aiLevel,
            ]);
        });
    }

    /**
     * Confirm step: user reviewed the suggestions; persist as confirmed memo.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function confirm(User $user, ConfirmUrlDTO $dto): Memo
    {
        $memo = Memo::find($dto->memoId);

        if (!$memo || $memo->type !== 'url') {
            throw new ResourceNotFoundException('Memo');
        }

        if ($memo->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $this->planLimit->assertCanCreateMemo($user);

        return DB::transaction(function () use ($user, $memo, $dto) {
            $memo->update([
                'status'   => 'confirmed',
                'title'    => $dto->title,
                'summary'  => $dto->summary,
                'keywords' => $dto->keywords,
            ]);

            $this->incrementUsage($user);

            return $memo->fresh();
        });
    }

    /**
     * Direct creation without review.
     */
    public function create(User $user, CreateUrlDTO $dto): Memo
    {
        $this->planLimit->assertCanCreateMemo($user);

        $aiOutput = $dto->aiLevel !== 'none'
            ? $this->ai->extractAndSummarizeUrl($dto->url, $dto->aiLevel)
            : null;

        return DB::transaction(function () use ($user, $dto, $aiOutput) {
            $memo = Memo::create([
                'user_id'    => $user->id,
                'group_id'   => $dto->groupId,
                'type'       => 'url',
                'status'     => 'confirmed',
                'source_url' => $dto->url,
                'title'      => $dto->title,
                'content'    => $aiOutput?->extractedContent,
                'summary'    => $dto->summary ?? $aiOutput?->summary,
                'keywords'   => $dto->keywords ?? $aiOutput?->keywords,
                'ai_level'   => $dto->aiLevel,
            ]);

            $this->incrementUsage($user);

            return $memo;
        });
    }

    private function incrementUsage(User $user): void
    {
        MonthlyUsage::updateOrCreate(
            [
                'user_id'  => $user->id,
                'group_id' => $user->active_workspace_group_id,
                'year'     => now()->year,
                'month'    => now()->month,
            ],
            []
        );

        MonthlyUsage::where('user_id', $user->id)
            ->where('group_id', $user->active_workspace_group_id)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->increment('memos_created_count');
    }
}
