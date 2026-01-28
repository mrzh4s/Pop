<?php
namespace Features\Auth\Login;

use Features\Auth\Shared\Ports\UserRepositoryInterface;
use Features\Auth\Shared\Exceptions\InvalidCredentialsException;

class LoginHandler
{
    private UserRepositoryInterface $repo;

    public function __construct(UserRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function handle(LoginCommand $command, string $ipAddress): array
    {
        $user = $this->repo->findByEmail($command->email);

        if (!$user) throw new InvalidCredentialsException("Invalid email or password.");

        $user->verifyPassword($command->password);

        $sessionId = bin2hex(random_bytes(16));
        $payload = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'groups' => $user->getGroups()
        ];

        $this->repo->saveSession($user->getId(), $sessionId, $payload, $ipAddress);

        return [
            'session_id' => $sessionId,
            'user' => $payload
        ];
    }
}
