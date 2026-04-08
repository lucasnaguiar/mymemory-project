<?php

namespace App\DTOs\Memo;

final class UpdateMemoDTO
{
    /**
     * @param  string[]|null  $keywords
     */
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $summary,
        public readonly ?array $keywords,
        public readonly ?string $content,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: array_key_exists('title', $data) ? $data['title'] : null,
            summary: array_key_exists('summary', $data) ? $data['summary'] : null,
            keywords: array_key_exists('keywords', $data) ? (array) $data['keywords'] : null,
            content: array_key_exists('content', $data) ? $data['content'] : null,
        );
    }

    public function toUpdateArray(): array
    {
        $data = [];
        if ($this->title !== null)    $data['title']    = $this->title;
        if ($this->summary !== null)  $data['summary']  = $this->summary;
        if ($this->keywords !== null) $data['keywords'] = $this->keywords;
        if ($this->content !== null)  $data['content']  = $this->content;
        return $data;
    }
}
