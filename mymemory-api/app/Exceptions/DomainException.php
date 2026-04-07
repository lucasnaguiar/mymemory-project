<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for all application domain exceptions.
 * Subclasses map to specific HTTP status codes via the exception Handler.
 */
abstract class DomainException extends RuntimeException
{
    public function __construct(string $message = '', private readonly string $errorCode = '')
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
