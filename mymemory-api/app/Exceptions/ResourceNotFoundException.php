<?php

namespace App\Exceptions;

final class ResourceNotFoundException extends DomainException
{
    public function __construct(string $resource = 'Resource', string $errorCode = 'not_found')
    {
        parent::__construct("{$resource} not found.", $errorCode);
    }
}
