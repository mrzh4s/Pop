<?php
namespace Features\Auth\Login;

use Features\Auth\Login\LoginCommand;
use Features\Auth\Login\LoginHandler;
use Features\Auth\Shared\Exceptions\InvalidCredentialsException;

class LoginController
{
    private LoginHandler $handler;

    public function __construct(LoginHandler $handler)
    {
        $this->handler = $handler;
    }

    public function login(array $request, string $ipAddress)
    {
        try {
            $command = new LoginCommand($request['email'], $request['password']);
            $result = $this->handler->handle($command, $ipAddress);

            return [
                'status' => 'success',
                'data' => $result
            ];
        } catch (InvalidCredentialsException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
