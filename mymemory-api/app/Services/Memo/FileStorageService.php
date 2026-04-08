<?php

namespace App\Services\Memo;

use App\Models\Memo;
use App\Models\MemoFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    /**
     * Store an uploaded file for a memo and create the MemoFile record.
     */
    public function store(UploadedFile $file, Memo $memo): MemoFile
    {
        $disk   = config('filesystems.default', 'local');
        $prefix = "memos/{$memo->user_id}/{$memo->id}";
        $name   = $file->hashName();

        Storage::disk($disk)->putFileAs($prefix, $file, $name);

        return MemoFile::create([
            'memo_id'           => $memo->id,
            'disk'              => $disk,
            'storage_key'       => "{$prefix}/{$name}",
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
        Storage::disk($file->disk)->delete($file->storage_key);
    }
}
