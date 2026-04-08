<?php

namespace App\Services\Memo;

use App\DTOs\Memo\AiOutputDTO;

class AudioMemoService extends MediaMemoBase
{
    protected function memoType(): string { return 'audio'; }

    protected function runAi(string $localPath, string $mimeType, string $level): AiOutputDTO
    {
        return $this->ai->transcribeAudio($localPath, $level);
    }
}
