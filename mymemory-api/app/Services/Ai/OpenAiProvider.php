<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI-backed AI provider.
 * Activated when AI_PROVIDER=openai and OPENAI_API_KEY is set.
 * Requires: composer require openai-php/laravel (Etapa 5+).
 */
class OpenAiProvider implements AiProviderInterface
{
    private const MODEL = 'gpt-4o-mini';
    private const CREDITS_PER_1K_TOKENS = 1;

    public function __construct(private readonly string $apiKey) {}

    public function summarizeText(string $content, string $level): AiOutputDTO
    {
        if ($level === 'none' || trim($content) === '') {
            return AiOutputDTO::empty();
        }

        $prompt = $level === 'basic'
            ? "Extract up to 8 key words or phrases from the following text. Return them as a comma-separated list only.\n\n{$content}"
            : "Summarize the following text concisely (max 300 chars) and extract up to 8 keywords. Respond in JSON: {\"summary\":\"...\",\"keywords\":[...]}.\n\n{$content}";

        $response = $this->chat($prompt);

        return $this->parseResponse($response, $content, $level);
    }

    public function extractAndSummarizeUrl(string $url, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $html    = $this->fetchUrl($url);
        $text    = $this->stripHtml($html);
        $summary = $this->summarizeText($text, $level);

        return new AiOutputDTO(
            summary: $summary->summary,
            keywords: $summary->keywords,
            extractedContent: mb_substr($text, 0, 5000),
            apiCreditsCost: $summary->apiCreditsCost,
        );
    }

    // -------------------------------------------------------------------------

    private function chat(string $prompt): array
    {
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => self::MODEL,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json();
    }

    private function parseResponse(array $response, string $content, string $level): AiOutputDTO
    {
        $text   = $response['choices'][0]['message']['content'] ?? '';
        $tokens = $response['usage']['total_tokens'] ?? 0;
        $cost   = ($tokens / 1000) * self::CREDITS_PER_1K_TOKENS;

        if ($level === 'basic') {
            $keywords = array_map('trim', explode(',', $text));
            return new AiOutputDTO('', array_filter($keywords), $content, $cost);
        }

        $decoded = json_decode($text, true);
        return new AiOutputDTO(
            summary: $decoded['summary'] ?? mb_substr($text, 0, 300),
            keywords: $decoded['keywords'] ?? [],
            extractedContent: $content,
            apiCreditsCost: $cost,
        );
    }

    private function fetchUrl(string $url): string
    {
        $response = Http::timeout(10)->get($url);
        if (!$response->successful()) {
            throw new RuntimeException("Failed to fetch URL: {$url}");
        }
        return $response->body();
    }

    private function stripHtml(string $html): string
    {
        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text) ?? $text;
        $text = strip_tags($text);
        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }
}
