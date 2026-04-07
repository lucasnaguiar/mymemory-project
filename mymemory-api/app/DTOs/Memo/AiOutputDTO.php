<?php

namespace App\DTOs\Memo;

final class AiOutputDTO
{
    /**
     * @param  string[]  $keywords
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $keywords,
        public readonly string $extractedContent,
        public readonly float $apiCreditsCost,
    ) {}

    public static function empty(): self
    {
        return new self('', [], '', 0.0);
    }
}
