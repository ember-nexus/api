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
        $containerLogOffset = $this->getContainerLogSize();

        $response = $this->runGetRequest(sprintf('/%s', self::FORBIDDEN_DATA), self::TOKEN_WITHOUT_ACCESS);
        $this->assertIsProblemResponse($response, 404);

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'Access to element was denied.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString(self::FORBIDDEN_DATA, $auditLogLines);
        $this->assertStringContainsString('"accessType":"READ"', $auditLogLines);
    }

    public function testGetMeEndpointIsAuditLogged(): void
    {
        $containerLogOffset = $this->getContainerLogSize();

        $response = $this->runGetRequest('/me', self::ACTIVE_TOKEN);
        $this->assertIsNodeResponse($response, 'User');

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'User/token endpoint was called.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString('"route":"get-me"', $auditLogLines);
    }

    public function testGetTokenEndpointIsAuditLogged(): void
    {
        $containerLogOffset = $this->getContainerLogSize();

        $response = $this->runGetRequest('/token', self::ACTIVE_TOKEN);
        $this->assertIsNodeResponse($response, 'Token');

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'User/token endpoint was called.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString('"route":"get-token"', $auditLogLines);
    }
}
