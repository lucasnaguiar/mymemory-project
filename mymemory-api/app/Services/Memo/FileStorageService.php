<?php

namespace App\Services\Memo;

use App\Logging\AiOperationLogger;
use App\Models\Memo;
use App\Models\MemoFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileStorageService
{
    /**
     * Store an uploaded file for a memo and create the MemoFile record.
     */
    public function store(UploadedFile $file, Memo $memo): MemoFile
    {
        $disk       = config('filesystems.default', 'local');
        $prefix     = "memos/{$memo->user_id}/{$memo->id}";
        $name       = $file->hashName();
        $storageKey = "{$prefix}/{$name}";

        try {
            Storage::disk($disk)->putFileAs($prefix, $file, $name);
            AiOperationLogger::storageSuccess('store', $disk, $storageKey, (int) $file->getSize());
        } catch (Throwable $e) {
            AiOperationLogger::storageError('store', $disk, $storageKey, $e);
            throw $e;
        }

        return MemoFile::create([
            'memo_id'           => $memo->id,
            'disk'              => $disk,
            'storage_key'       => $storageKey,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes'        => $file->getSize(),
        ]);
    }

    /**
     * Get the absolute local path for a stored file (useful for AI processing).
     * For S3 or other remote disks, the file would need to be downloaded first.
     */
    public function getLocalPath(MemoFile $file): string
    {
        return Storage::disk($file->disk)->path($file->storage_key);
    }

    /**
     * Delete the stored file from disk.
     */
    public function delete(MemoFile $file): void
    {
        try {
            Storage::disk($file->disk)->delete($file->storage_key);
            AiOperationLogger::storageSuccess('delete', $file->disk, $file->storage_key);
        } catch (Throwable $e) {
            AiOperationLogger::storageError('delete', $file->disk, $file->storage_key, $e);
            throw $e;
        }
    }
}
