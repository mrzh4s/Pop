<?php
namespace Features\Auth\Shared\Adapters;

use Features\Auth\Shared\Ports\UserRepositoryInterface;
use Features\Auth\Shared\Domain\User;
use PDO;

class PgUserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM auth.user_summary
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $roles = $row['roles'] ? explode(', ', $row['roles']) : [];
        $groups = $row['groups'] ? explode(', ', $row['groups']) : [];

        return new User($row['id'], $row['name'], $row['email'], $row['password'] ?? '', $roles, $groups);
    }

    public function saveSession(string $userId, string $sessionId, array $payload, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO auth.sessions 
            (session_id, user_id, ip_address, payload, last_activity, is_current, created_at, updated_at)
            VALUES
            (:session_id, :user_id, :ip_address, :payload::jsonb, :last_activity, true, NOW(), NOW())
        ");
        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'payload' => json_encode($payload),
            'last_activity' => time()
        ]);
    }
}
