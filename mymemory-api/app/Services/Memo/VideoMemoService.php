<?php

namespace App\Services\Memo;

use App\DTOs\Memo\AiOutputDTO;

class VideoMemoService extends MediaMemoBase
{
    protected function memoType(): string { return 'video'; }

    protected function runAi(string $localPath, string $mimeType, string $level): AiOutputDTO
    {
        return $this->ai->transcribeVideo($localPath, $level);
    }
}
