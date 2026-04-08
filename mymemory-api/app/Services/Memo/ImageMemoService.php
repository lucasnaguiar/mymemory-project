<?php

namespace App\Services\Memo;

use App\DTOs\Memo\AiOutputDTO;

class ImageMemoService extends MediaMemoBase
{
    protected function memoType(): string { return 'image'; }

    protected function runAi(string $localPath, string $mimeType, string $level): AiOutputDTO
    {
        return $this->ai->analyzeImage($localPath, $level);
    }
}
