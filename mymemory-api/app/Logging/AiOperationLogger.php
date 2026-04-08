<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Centralised logger for AI provider calls and storage operations.
 *
 * Writes to the dedicated 'ai' channel (storage/logs/ai.log, rotated daily).
 * All methods are static for easy use anywhere without DI.
 */
class AiOperationLogger
{
    private static string $channel = 'ai';

    // ---------------------------------------------------------------------------
    // AI operations
    // ---------------------------------------------------------------------------

    public static function aiStart(string $operation, array $context = []): void
    {
        Log::channel(self::$channel)->debug("[AI] START {$operation}", $context);
    }

    public static function aiSuccess(string $operation, float $durationMs, array $context = []): void
    {
        Log::channel(self::$channel)->info("[AI] OK {$operation} ({$durationMs}ms)", $context);
    }

    public static function aiError(string $operation, Throwable $e, array $context = []): void
    {
        Log::channel(self::$channel)->error("[AI] ERROR {$operation}: {$e->getMessage()}", array_merge($context, [
            'exception' => get_class($e),
            'trace'     => $e->getTraceAsString(),
        ]));
    }

    // ---------------------------------------------------------------------------
    // Storage operations
    // ---------------------------------------------------------------------------

    public static function storageSuccess(string $operation, string $disk, string $path, int $sizeBytes = 0): void
    {
        Log::channel(self::$channel)->info("[STORAGE] OK {$operation}", [
            'disk'       => $disk,
            'path'       => $path,
            'size_bytes' => $sizeBytes,
        ]);
    }

    public static function storageError(string $operation, string $disk, string $path, Throwable $e): void
    {
        Log::channel(self::$channel)->error("[STORAGE] ERROR {$operation}", [
            'disk'      => $disk,
            'path'      => $path,
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
        ]);
    }
}
