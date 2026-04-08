<?php

namespace App\DTOs\Group;

final class AcceptInviteDTO
{
    public function __construct(
        public readonly string $token,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(token: trim($data['token']));
    }
}
