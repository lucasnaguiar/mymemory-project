<?php

namespace App\Exceptions;

final class AuthorizationException extends DomainException
{
    public function __construct(string $message = 'You are not authorized to perform this action.', string $errorCode = 'forbidden')
    {
        parent::__construct($message, $errorCode);
    }
}
