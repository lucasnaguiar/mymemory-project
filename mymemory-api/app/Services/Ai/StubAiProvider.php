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

        return new AiOutputDTO(
            summary: $summary,
            keywords: [$host, 'url', 'stub'],
            extractedContent: "Extracted from: {$url}",
            apiCreditsCost: 0.0,
        );
    }

    public function analyzeImage(string $filePath, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $filename = basename($filePath);
        $summary  = "[Stub] Imagem analisada: {$filename}.";
        $ocr      = $level === 'full' ? "[Stub OCR] Texto extraído da imagem {$filename}." : '';

        return new AiOutputDTO(
            summary: $summary,
            keywords: ['imagem', 'stub', pathinfo($filename, PATHINFO_EXTENSION)],
            extractedContent: $ocr,
            apiCreditsCost: 0.0,
        );
    }

    public function transcribeAudio(string $filePath, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $filename     = basename($filePath);
        $transcription = "[Stub] Transcrição do áudio {$filename}: Lorem ipsum dolor sit amet.";
        $summary      = "[Stub] Resumo: conteúdo de áudio de {$filename}.";

        return new AiOutputDTO(
            summary: $summary,
            keywords: ['audio', 'stub', pathinfo($filename, PATHINFO_EXTENSION)],
            extractedContent: $transcription,
            apiCreditsCost: 0.0,
        );
    }

    public function transcribeVideo(string $filePath, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $filename     = basename($filePath);
        $transcription = "[Stub] Transcrição do vídeo {$filename}: Lorem ipsum dolor sit amet.";
        $summary      = "[Stub] Resumo: conteúdo de vídeo de {$filename}.";

        return new AiOutputDTO(
            summary: $summary,
            keywords: ['video', 'stub', pathinfo($filename, PATHINFO_EXTENSION)],
            extractedContent: $transcription,
            apiCreditsCost: 0.0,
        );
    }

    public function extractDocument(string $filePath, string $mimeType, string $level): AiOutputDTO
    {
        if ($level === 'none') {
            return AiOutputDTO::empty();
        }

        $filename  = basename($filePath);
        $extracted = "[Stub] Texto extraído do documento {$filename}: Lorem ipsum dolor sit amet.";
        $summary   = "[Stub] Resumo do documento {$filename}.";

        return new AiOutputDTO(
            summary: $summary,
            keywords: ['documento', 'stub', pathinfo($filename, PATHINFO_EXTENSION)],
            extractedContent: $extracted,
            apiCreditsCost: 0.0,
        );
    }

    public function generateSearchSynonyms(string $query): array
    {
        // Stub: return empty so search works without AI in dev
        return [];
    }
}
