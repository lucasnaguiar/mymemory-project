<?php

namespace App\Contracts;

use App\DTOs\Memo\AiOutputDTO;

/**
 * Contract for AI providers (text summary, URL extraction).
 * Implementations must be injected; never instantiated directly.
 */
interface AiProviderInterface
{
    /**
     * Summarize plain text content.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function summarizeText(string $content, string $level): AiOutputDTO;

    /**
     * Fetch a URL, extract its text content, and summarize it.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function extractAndSummarizeUrl(string $url, string $level): AiOutputDTO;
}
