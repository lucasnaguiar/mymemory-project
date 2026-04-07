<?php

namespace App\Exceptions;

/**
 * Thrown when a business rule is violated (e.g. plan limits exceeded).
 * Maps to HTTP 422 Unprocessable Entity.
 */
final class BusinessRuleException extends DomainException
{
    public function __construct(string $message, string $errorCode = 'business_rule_violation')
    {
        parent::__construct($message, $errorCode);
    }
}
