<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;

/**
 * Stub AI provider for local development and testing.
 * Returns deterministic outputs without calling any external API.
 */
class StubAiProvider implements AiProviderInterface
{
    public function summarizeText(string $content, string $level): AiOutputDTO
    {
        if ($level === 'none' || trim($content) === '') {
            return AiOutputDTO::empty();
        }

        $words    = preg_split('/\s+/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        $keywords = array_slice($words, 0, min(5, count($words)));

        $summary = $level === 'basic'
            ? implode(', ', $keywords)
            : '[Stub summary] ' . mb_substr($content, 0, 200);

        return new AiOutputDTO(
            summary: $summary,
            keywords: $keywords,
            extractedContent: $content,
            apiCreditsCost: 0.0,
        );
    }

    public function extractAndSummarizeUrl(string $url, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $host    = parse_url($url, PHP_URL_HOST) ?? $url;
        $summary = "[Stub] Conteúdo extraído de {$host}.";
        $keywords = [$host, 'url', 'stub'];

        return new AiOutputDTO(
            summary: $summary,
            keywords: $keywords,
            extractedContent: "Extracted from: {$url}",
            apiCreditsCost: 0.0,
        );
    }
}
