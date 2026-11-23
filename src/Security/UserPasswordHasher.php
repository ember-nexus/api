<?php

declare(strict_types=1);

namespace App\Security;

class UserPasswordHasher
{
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword(string $password, string $passwordHash): bool
    {
        return password_verify($password, $passwordHash);
    }

    public function needsRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_ARGON2ID);
    }

    private ?string $dummyHash = null;

    /**
     * Spends the time of a real password verification without a real account. Used when the account does not exist, so
     * that the response time does not tell whether an identifier is registered. The dummy hash is created with the
     * current algorithm and parameters, once per process.
     */
    public function verifyAgainstDummyHash(string $password): void
    {
        $this->dummyHash ??= $this->hashPassword(bin2hex(random_bytes(16)));
        $this->verifyPassword($password, $this->dummyHash);
    }
}
