<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * Regression test for https://github.com/ember-nexus/api/issues/347: requests denied for missing permissions, and
 * calls to user/token management endpoints, must be logged on the dedicated `audit` channel, which (unlike regular
 * request logging) is always flushed, not just when an error also occurs in the same request.
 */
class AuditLogTest extends BaseRequestTestCase
{
    // taken from the "security.mixedGroupOwnership" / "99_02" scenario: a node this token has no access to at all
    private const string TOKEN_WITHOUT_ACCESS = 'secret-token:SaUQctGYKDHdRnm1jj7pG5';
    private const string FORBIDDEN_DATA = '23f86352-4382-4fbd-bc6b-848fa3eadae0';

    private const string ACTIVE_TOKEN = 'secret-token:VWPtCCQskD6uQnf0CHdNjY';

    public function testAccessDeniedRequestIsAuditLogged(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runGetRequest(sprintf('/%s', self::FORBIDDEN_DATA), self::TOKEN_WITHOUT_ACCESS);
        $this->assertIsProblemResponse($response, 404);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'Access to element was denied.');
        $this->assertStringContainsString(self::FORBIDDEN_DATA, $auditLogEntry);
        $this->assertStringContainsString('"accessType":"READ"', $auditLogEntry);
    }

    public function testGetMeEndpointIsAuditLogged(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runGetRequest('/me', self::ACTIVE_TOKEN);
        $this->assertIsNodeResponse($response, 'User');

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'User/token endpoint was called.');
        $this->assertStringContainsString('"route":"get-me"', $auditLogEntry);
    }

    public function testGetTokenEndpointIsAuditLogged(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runGetRequest('/token', self::ACTIVE_TOKEN);
        $this->assertIsNodeResponse($response, 'Token');

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'User/token endpoint was called.');
        $this->assertStringContainsString('"route":"get-token"', $auditLogEntry);
    }
}
