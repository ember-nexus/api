<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\TokenRevokeEntry;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Safe\DateTime;

#[Small]
#[CoversClass(TokenRevokeEntry::class)]
class TokenRevokeEntryTest extends TestCase
{
    public function testTokenRevokeEntry(): void
    {
        $tokenId = Uuid::fromString('02IiNr0G8viOVKl7vZhJTA9SWT5I2U2YzdpqD');
        $tokenCreated = DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-19 00:00:00');
        $tokenExpirationDate = DateTime::createFromFormat('Y-m-d H:i:s', '2025-01-19 00:00:00');
        $userId = Uuid::fromString('02IiNr0G8viOVKjqnRJfymAHgG5TF2M4NdvAP');
        $userUniqueIdentifier = 'user@localhost';
        $tokenHash = 'someTokenHash';

        $tokenRevokeEntry = new TokenRevokeEntry(
            $tokenId,
            $tokenCreated,
            $tokenExpirationDate,
            $userId,
            $userUniqueIdentifier,
            $tokenHash
        );

        $this->assertSame($tokenId, $tokenRevokeEntry->getTokenId());
        $this->assertSame($tokenCreated, $tokenRevokeEntry->getTokenCreated());
        $this->assertSame($tokenExpirationDate, $tokenRevokeEntry->getTokenExpirationDate());
        $this->assertSame($userId, $tokenRevokeEntry->getUserId());
        $this->assertSame($userUniqueIdentifier, $tokenRevokeEntry->getUserUniqueIdentifier());
        $this->assertSame($tokenHash, $tokenRevokeEntry->getTokenHash());
    }
}
