<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\DTOs\Memo\AiOutputDTO;
use App\Logging\AiOperationLogger;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

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
        $start = microtime(true);
        AiOperationLogger::aiStart('chat', ['model' => self::MODEL, 'prompt_len' => strlen($prompt)]);

        try {
            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'    => self::MODEL,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('OpenAI API error: ' . $response->body());
            }

            $data = $response->json();
            AiOperationLogger::aiSuccess('chat', round((microtime(true) - $start) * 1000), [
                'tokens' => $data['usage']['total_tokens'] ?? 0,
            ]);

            return $data;
        } catch (Throwable $e) {
            AiOperationLogger::aiError('chat', $e);
            throw $e;
        }
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

    // -------------------------------------------------------------------------
    // Media methods — Etapa 5
    // -------------------------------------------------------------------------

    public function analyzeImage(string $filePath, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $base64   = base64_encode((string) file_get_contents($filePath));
        $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        $dataUrl  = "data:{$mimeType};base64,{$base64}";

        $prompt = $level === 'basic'
            ? 'Describe this image briefly in one sentence and list up to 5 keywords as comma-separated values.'
            : 'Analyze this image: describe it concisely (max 300 chars), extract any text (OCR), and list up to 8 keywords. Respond in JSON: {"summary":"...","ocr":"...","keywords":["..."]}.';

        $response = $this->chatWithImage($prompt, $dataUrl);

        return $this->parseMediaResponse($response, $level);
    }

    public function transcribeAudio(string $filePath, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $transcription = $this->whisper($filePath);

        if ($level === 'basic') {
            $keywords = array_slice(explode(' ', $transcription), 0, 8);
            return new AiOutputDTO('', $keywords, $transcription, 0.0);
        }

        $summaryOutput = $this->summarizeText($transcription, 'full');
        return new AiOutputDTO(
            summary: $summaryOutput->summary,
            keywords: $summaryOutput->keywords,
            extractedContent: $transcription,
            apiCreditsCost: $summaryOutput->apiCreditsCost,
        );
    }

    public function transcribeVideo(string $filePath, string $level): AiOutputDTO
    {
        // Same pipeline as audio (OpenAI Whisper supports video files too)
        return $this->transcribeAudio($filePath, $level);
    }

    public function extractDocument(string $filePath, string $mimeType, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        // Attempt plain text read (works for .txt, .eml, .msg with basic parsing)
        $text = @file_get_contents($filePath);
        if (!$text) {
            return AiOutputDTO::empty();
        }

        // Strip binary for PDF/DOCX — the real extractor (Etapa 5+) does this properly
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFF]/', ' ', $text) ?? $text;
        $text = mb_substr($text, 0, 8000);

        return $this->summarizeText($text, $level);
    }

    // -------------------------------------------------------------------------

    private function chatWithImage(string $prompt, string $dataUrl): array
    {
        $start = microtime(true);
        AiOperationLogger::aiStart('chatWithImage', ['model' => 'gpt-4o']);

        try {
            $response = Http::withToken($this->apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'    => 'gpt-4o',
                    'messages' => [[
                        'role'    => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                        ],
                    ]],
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('OpenAI Vision error: ' . $response->body());
            }

            $data = $response->json();
            AiOperationLogger::aiSuccess('chatWithImage', round((microtime(true) - $start) * 1000), [
                'tokens' => $data['usage']['total_tokens'] ?? 0,
            ]);

            return $data;
        } catch (Throwable $e) {
            AiOperationLogger::aiError('chatWithImage', $e);
            throw $e;
        }
    }

    private function whisper(string $filePath): string
    {
        $start = microtime(true);
        AiOperationLogger::aiStart('whisper', ['file' => basename($filePath)]);

        try {
            $response = Http::withToken($this->apiKey)
                ->attach('file', (string) file_get_contents($filePath), basename($filePath))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('OpenAI Whisper error: ' . $response->body());
            }

            $text = $response->json('text', '');
            AiOperationLogger::aiSuccess('whisper', round((microtime(true) - $start) * 1000), [
                'transcription_len' => strlen($text),
            ]);

            return $text;
        } catch (Throwable $e) {
            AiOperationLogger::aiError('whisper', $e);
            throw $e;
        }
    }

    private function parseMediaResponse(array $response, string $level): AiOutputDTO
    {
        $text   = $response['choices'][0]['message']['content'] ?? '';
        $tokens = $response['usage']['total_tokens'] ?? 0;
        $cost   = ($tokens / 1000) * self::CREDITS_PER_1K_TOKENS;

        if ($level === 'basic') {
            $keywords = array_map('trim', explode(',', $text));
            return new AiOutputDTO('', array_filter($keywords), '', $cost);
        }

        $decoded = json_decode($text, true);
        return new AiOutputDTO(
            summary: $decoded['summary'] ?? mb_substr($text, 0, 300),
            keywords: $decoded['keywords'] ?? [],
            extractedContent: $decoded['ocr'] ?? '',
            apiCreditsCost: $cost,
        );
    }

    public function generateSearchSynonyms(string $query): array
    {
        $prompt   = "Generate up to 6 synonyms or related search terms for the query: \"{$query}\". Return a JSON array of strings only.";
        $response = $this->chat($prompt);
        $text     = $response['choices'][0]['message']['content'] ?? '[]';

        $decoded = json_decode($text, true);
        return is_array($decoded) ? array_filter($decoded, 'is_string') : [];
    }

    private function stripHtml(string $html): string
    {
        $text = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text) ?? $text;
        $text = strip_tags($text);
        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }
}
