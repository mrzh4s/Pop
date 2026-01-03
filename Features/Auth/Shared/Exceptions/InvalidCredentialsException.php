<?php

namespace Features\Auth\Shared\Exceptions;

class InvalidCredentialsException extends \Exception
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message);
    }
}