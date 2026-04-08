<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\MemoFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * GET /api/v1/media/{authorId}/{memoId}/{fileName}
     *
     * Protected file serving: the requester must be authenticated and must either
     * own the memo OR be a member of the group the memo belongs to.
     */
    public function serve(Request $request, int $authorId, int $memoId, string $fileName): mixed
    {
        $storageKey = "memos/{$authorId}/{$memoId}/{$fileName}";

        $memoFile = MemoFile::with(['memo.group.members'])
            ->where('storage_key', $storageKey)
            ->first();

        if (!$memoFile) {
            throw new ResourceNotFoundException('Arquivo');
        }

        $memo = $memoFile->memo;

        if (!$memo) {
            throw new ResourceNotFoundException('Memo');
        }

        $user = $request->user();

        // Authorization: owner of the memo OR member of the memo's group
        $isOwner   = $memo->user_id === $user->id;
        $isMember  = $memo->group_id !== null
            && $memo->group
            && $memo->group->members->contains('user_id', $user->id);

        if (!$isOwner && !$isMember) {
            throw new AuthorizationException('Sem permissão para acessar este arquivo.');
        }

        $disk = Storage::disk($memoFile->disk);

        if (!$disk->exists($storageKey)) {
            throw new ResourceNotFoundException('Arquivo no storage');
        }

        return $disk->download($storageKey, $memoFile->original_filename, [
            'Content-Type'  => $memoFile->mime_type,
        ]);
    }
}
