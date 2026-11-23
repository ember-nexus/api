<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\Security\AuditFindings\AuditHelperTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Executes `cron:expire-tokens`: active tokens past their expiration date become `EXPIRED`, tokens which expired longer
 * ago than `tokenDeleteExpiredTokensAutomaticallyInSeconds` (default: 14 days) are deleted.
 */
#[Group('command')]
class CronExpireTokensTest extends BaseCronTestCase
{
    use AuditHelperTrait;

    private function tokenState(string $userId, string $secret): ?string
    {
        $rows = $this->getCypherClient()->run(
            'MATCH (:User {id: $userId})-[:OWNS]->(t:Token {hash: $hash}) RETURN t.state AS state',
            ['userId' => $userId, 'hash' => hash('sha3-256', $secret)]
        )->toArray();

        return [] === $rows ? null : (string) $rows[0]->get('state');
    }

    private function expireToken(string $userId, string $secret, string $duration): void
    {
        $this->getCypherClient()->run(
            'MATCH (:User {id: $userId})-[:OWNS]->(t:Token {hash: $hash}) SET t.expirationDate = datetime() - duration($duration)',
            ['userId' => $userId, 'hash' => hash('sha3-256', $secret), 'duration' => $duration]
        );
    }

    public function testExpiredTokensAreMarkedAndOldOnesDeleted(): void
    {
        $user = $this->registerUser('cron-tokens');
        $recentlyExpired = $this->createTokenFor($user['email']);
        $longExpired = $this->createTokenFor($user['email']);
        $this->expireToken($user['id'], $recentlyExpired, 'PT1H');
        $this->expireToken($user['id'], $longExpired, 'P30D');

        [$exitCode, $output] = $this->runConsoleCommand('cron:expire-tokens');

        $this->assertSame(0, $exitCode, $output);
        $this->assertSame('ACTIVE', $this->tokenState($user['id'], $user['token']));
        $this->assertSame('EXPIRED', $this->tokenState($user['id'], $recentlyExpired));
        $this->assertNull($this->tokenState($user['id'], $longExpired));
        $this->assertSame(200, $this->runGetRequest('/me', $user['token'])->getStatusCode());
        $this->assertSame(401, $this->runGetRequest('/me', $recentlyExpired)->getStatusCode());
    }
}
