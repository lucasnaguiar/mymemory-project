<?php

namespace App\DTOs\Group;

final class CreateInviteDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim($data['email'])),
            role:  $data['role'] ?? 'viewer',
        );
    }
}
