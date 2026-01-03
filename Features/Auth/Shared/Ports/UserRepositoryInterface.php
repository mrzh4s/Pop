<?php
namespace Features\Auth\Shared\Ports;

use Features\Auth\Shared\Domain\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function saveSession(string $userId, string $sessionId, array $payload, string $ipAddress): void;
}
