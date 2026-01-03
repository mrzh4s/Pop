<?php
namespace Features\Auth\Login;

class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
