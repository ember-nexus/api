<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\TokenRevokeEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(TokenRevokeEntry::class)]
class TokenRevokeEntryTest extends TestCase
{
    public function testTokenRevokeEntry(): void
    {
        $tokenId = Uuid::fromString('e36d83dc-fffe-4163-a416-aace96cc5065');
        $tokenCreated = DateTime::createFromFormat('Y-m-d H:i:s', '2024-01-19 00:00:00');
        $tokenExpirationDate = DateTime::createFromFormat('Y-m-d H:i:s', '2025-01-19 00:00:00');
        $userId = Uuid::fromString('40ecca67-4cf0-4c9b-b429-5add9c57ded2');
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
