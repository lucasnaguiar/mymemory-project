<?php

namespace App\Contracts;

use App\DTOs\Memo\AiOutputDTO;

/**
 * Contract for AI providers (text, URL, image, audio, video, document).
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

    /**
     * Analyze an image file (OCR + vision description).
     * $filePath is the absolute local path to the stored file.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function analyzeImage(string $filePath, string $level): AiOutputDTO;

    /**
     * Transcribe an audio file and summarize the transcript.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function transcribeAudio(string $filePath, string $level): AiOutputDTO;

    /**
     * Extract audio track from a video file, transcribe, and summarize.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function transcribeVideo(string $filePath, string $level): AiOutputDTO;

    /**
     * Extract text from a document (PDF, DOCX, etc.) and summarize.
     *
     * @param  'none'|'basic'|'full'  $level
     */
    public function extractDocument(string $filePath, string $mimeType, string $level): AiOutputDTO;

    /**
     * Generate search synonym expansions for the given query.
     * Returns an array of alternative terms or phrases.
     *
     * @return string[]
     */
    public function generateSearchSynonyms(string $query): array;
}
