<?php

namespace App\DTOs\Memo;

use Illuminate\Http\UploadedFile;

final class ProcessMediaDTO
{
    public function __construct(
        public readonly UploadedFile $file,
        public readonly string $aiLevel,
        public readonly ?int $groupId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            aiLevel: $data['ai_level'] ?? 'full',
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
