<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * @note Regression test for https://github.com/ember-nexus/api/issues/238: being a direct member of a group (via
 *       IS_IN_GROUP, without an additional OWNS relation) must grant CREATE/UPDATE/DELETE access to the group node
 *       itself, not just READ access.
 */
class _03_05_GroupImmediateWriteAccessTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:J0ik1otCHLbH08cd4C1ToB';

    private const string GROUP = '67f8978f-6205-4e95-a20c-e82fa3367f6a';

    private function getAuditLogPath(): string
    {
        return __DIR__.'/../../../var/log/audit.log';
    }

    private function getAuditLogSize(): int
    {
        return is_file($this->getAuditLogPath()) ? (int) filesize($this->getAuditLogPath()) : 0;
    }

    private function getAuditLogContentsSince(int $offset): string
    {
        if (!is_file($this->getAuditLogPath())) {
            return '';
        }
        $handle = fopen($this->getAuditLogPath(), 'rb');
        fseek($handle, $offset);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return false !== $contents ? $contents : '';
    }

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testCreateChildNodeDirectlyOnGroup(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPostRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Data created directly on group',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        // creating a child node is not itself a write against the group node, so it must not be audit-logged
        $this->assertStringNotContainsString(self::GROUP, $this->getAuditLogContentsSince($auditLogOffset));
    }

    public function testCreateOwnsRelationStartingAtGroup(): void
    {
        $dataResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Data owned by group through OWNS relation',
                ],
            ]
        );
        $this->assertIsCreatedResponse($dataResponse);
        $dataId = $this->getUuidFromLocation($dataResponse);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'OWNS',
                'start' => self::GROUP,
                'end' => $dataId,
            ]
        );
        $this->assertIsCreatedResponse($response);
    }

    public function testUpdateGroupThroughPatch(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPatchRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN,
            [
                'name' => 'Group updated through PATCH',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group updated through PATCH', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertStringContainsString('audit.NOTICE', $auditLogEntry);
        $this->assertStringContainsString('Group node was updated.', $auditLogEntry);
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }

    public function testUpdateGroupThroughPut(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPutRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN,
            [
                'name' => 'Group updated through PUT',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group updated through PUT', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertStringContainsString('audit.NOTICE', $auditLogEntry);
        $this->assertStringContainsString('Group node was updated.', $auditLogEntry);
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }

    public function testDeleteGroup(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runDeleteRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsDeletedResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertStringContainsString('audit.NOTICE', $auditLogEntry);
        $this->assertStringContainsString('Group node was deleted.', $auditLogEntry);
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }
}
