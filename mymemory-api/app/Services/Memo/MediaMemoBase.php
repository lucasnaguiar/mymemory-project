<?php

namespace App\Services\Memo;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\DTOs\Memo\ConfirmMediaDTO;
use App\DTOs\Memo\ProcessMediaDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Memo;
use App\Models\MonthlyUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class MediaMemoBase
{
    public function __construct(
        protected readonly AiProviderInterface $ai,
        protected readonly PlanLimitService $planLimit,
        protected readonly FileStorageService $storage,
        protected readonly MediaValidationService $mediaValidator,
    ) {}

    /**
     * The Eloquent memo type string (e.g. 'image', 'audio').
     */
    abstract protected function memoType(): string;

    /**
     * Run AI on the stored file and return the output.
     * Called after the file has been stored to disk.
     */
    abstract protected function runAi(string $localPath, string $mimeType, string $level): AiOutputDTO;

    /**
     * Process step: validate, store, run AI, return draft memo.
     */
    public function process(User $user, ProcessMediaDTO $dto): Memo
    {
        $this->mediaValidator->assertFileAllowed($dto->file, $this->memoType(), $user);

        $aiOutput = null;

        return DB::transaction(function () use ($user, $dto, &$aiOutput) {
            // Remove stale drafts for this user/type
            Memo::where('user_id', $user->id)
                ->where('type', $this->memoType())
                ->where('status', 'draft')
                ->where('group_id', $dto->groupId)
                ->delete();

            // Create draft first to get a stable ID for the storage path
            $memo = Memo::create([
                'user_id'  => $user->id,
                'group_id' => $dto->groupId,
                'type'     => $this->memoType(),
                'status'   => 'draft',
                'ai_level' => $dto->aiLevel,
            ]);

            // Store file and link it
            $memoFile  = $this->storage->store($dto->file, $memo);
            $localPath = $this->storage->getLocalPath($memoFile);

            // Run AI (silently fail to keep UX unblocked — user can edit on review page)
            $aiOutput = $dto->aiLevel !== 'none'
                ? $this->runAi($localPath, $memoFile->mime_type, $dto->aiLevel)
                : AiOutputDTO::empty();

            $memo->update([
                'content' => $aiOutput->extractedContent ?: null,
                'summary' => $aiOutput->summary ?: null,
                'keywords' => $aiOutput->keywords ?: null,
            ]);

            return $memo->load('file');
        });
    }

    /**
     * Confirm step: user reviewed AI suggestions; persist as confirmed memo.
     */
    public function confirm(User $user, ConfirmMediaDTO $dto): Memo
    {
        $memo = Memo::with('file')->find($dto->memoId);

        if (!$memo || $memo->type !== $this->memoType()) {
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

            return $memo->fresh('file');
        });
    }

    protected function incrementUsage(User $user): void
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
