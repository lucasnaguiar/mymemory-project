<?php

namespace App\Services\Memo;

use App\DTOs\Memo\UpdateMemoDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Memo;
use App\Models\User;

class MemoUpdateService
{
    /**
     * Update an existing memo's editable fields.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function update(User $user, int $memoId, UpdateMemoDTO $dto): Memo
    {
        $memo = Memo::with('file')->find($memoId);

        if (!$memo) {
            throw new ResourceNotFoundException('Memo');
        }

        if ($memo->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $updates = $dto->toUpdateArray();
        if (!empty($updates)) {
            $memo->update($updates);
        }

        return $memo->fresh('file');
    }

    /**
     * Soft-delete a memo.
     *
     * @throws ResourceNotFoundException
     * @throws AuthorizationException
     */
    public function destroy(User $user, int $memoId): void
    {
        $memo = Memo::find($memoId);

        if (!$memo) {
            throw new ResourceNotFoundException('Memo');
        }

        if ($memo->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $memo->delete();
    }
}
