<?php

namespace App\Services\Memo;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\ConfirmTextDTO;
use App\DTOs\Memo\CreateTextDTO;
use App\DTOs\Memo\ProcessTextDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Memo;
use App\Models\MonthlyUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TextMemoService
{
    public function __construct(
        private readonly AiProviderInterface $ai,
        private readonly PlanLimitService $planLimit,
    ) {}

    /**
     * Process step: run AI on the text and return a draft memo with the suggestion.
     * The draft will be cleaned up if never confirmed.
     */
    public function process(User $user, ProcessTextDTO $dto): Memo
    {
        $aiOutput = $this->ai->summarizeText($dto->content, $dto->aiLevel);

        return DB::transaction(function () use ($user, $dto, $aiOutput) {
            // Remove stale drafts for this user/type before creating a new one
            Memo::where('user_id', $user->id)
                ->where('type', 'text')
                ->where('status', 'draft')
                ->where('group_id', $dto->groupId)
                ->delete();

            return Memo::create([
                'user_id'  => $user->id,
                'group_id' => $dto->groupId,
                'type'     => 'text',
                'status'   => 'draft',
                'content'  => $dto->content,
                'summary'  => $aiOutput->summary ?: null,
                'keywords' => $aiOutput->keywords ?: null,
                'ai_level' => $dto->aiLevel,
            ]);
        });
    }

    /**
     * Confirm step: user reviewed AI suggestions; persist as confirmed memo.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function confirm(User $user, ConfirmTextDTO $dto): Memo
    {
        $memo = Memo::find($dto->memoId);

        if (!$memo || $memo->type !== 'text') {
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
                'content'  => $dto->content,
                'summary'  => $dto->summary,
                'keywords' => $dto->keywords,
            ]);

            $this->incrementUsage($user);

            return $memo->fresh();
        });
    }

    /**
     * Direct creation without the review step.
     */
    public function create(User $user, CreateTextDTO $dto): Memo
    {
        $this->planLimit->assertCanCreateMemo($user);

        $aiOutput = $dto->aiLevel !== 'none'
            ? $this->ai->summarizeText($dto->content, $dto->aiLevel)
            : null;

        return DB::transaction(function () use ($user, $dto, $aiOutput) {
            $memo = Memo::create([
                'user_id'  => $user->id,
                'group_id' => $dto->groupId,
                'type'     => 'text',
                'status'   => 'confirmed',
                'title'    => $dto->title,
                'content'  => $dto->content,
                'summary'  => $dto->summary ?? $aiOutput?->summary,
                'keywords' => $dto->keywords ?? $aiOutput?->keywords,
                'ai_level' => $dto->aiLevel,
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
