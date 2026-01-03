<?php
namespace Features\Auth\Shared\Domain;

use Features\Auth\Shared\Domain\Exceptions\InvalidCredentialsException;

class User
{
    private string $id;
    private string $name;
    private string $email;
    private string $hashedPassword;

    private array $roles = [];
    private array $groups = [];

    public function __construct(string $id, string $name, string $email, string $hashedPassword, array $roles = [], array $groups = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->hashedPassword = $hashedPassword;
        $this->roles = $roles;
        $this->groups = $groups;
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getRoles(): array { return $this->roles; }
    public function getGroups(): array { return $this->groups; }

    public function verifyPassword(string $password): void
    {
        if (!password_verify($password, $this->hashedPassword)) {
            throw new InvalidCredentialsException("Invalid email or password.");
        }
    }
}
