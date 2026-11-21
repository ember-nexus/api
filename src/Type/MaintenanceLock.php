<?php

declare(strict_types=1);

namespace App\Type;

/**
 * A claimed maintenance lock; the token proves ownership for release, refresh and ownership checks.
 */
final readonly class MaintenanceLock
{
    public function __construct(
        private MaintenanceLockType $type,
        private string $token,
    ) {
    }

    public function getType(): MaintenanceLockType
    {
        return $this->type;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
