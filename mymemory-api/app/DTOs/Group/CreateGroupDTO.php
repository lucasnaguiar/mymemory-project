<?php

namespace App\DTOs\Group;

final class CreateGroupDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int    $subscriptionPlanId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:               trim($data['name']),
            subscriptionPlanId: (int) $data['subscription_plan_id'],
        );
    }
}
