<?php

namespace App\Security;

class UserPasswordHasher
{
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }
}
