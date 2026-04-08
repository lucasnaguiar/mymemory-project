<?php

namespace App\Services\Memo;

use App\DTOs\Memo\AiOutputDTO;

class DocumentMemoService extends MediaMemoBase
{
    protected function memoType(): string { return 'document'; }

    protected function runAi(string $localPath, string $mimeType, string $level): AiOutputDTO
    {
        return $this->ai->extractDocument($localPath, $mimeType, $level);
    }
}
