<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _03_05_GroupImmediateWriteAccessTest extends BaseRequestTestCase
{
    private const string TOKEN_MEMBER = 'secret-token:J0ik1otCHLbH08cd4C1ToB';

    private const string TOKEN_OWNER = 'secret-token:77G6kkMk5hiQqRSZaJyrXK';

    private const string TOKEN_ACCESS = 'secret-token:oRACZjdcNS5xpdEq7LmC3H';

    private const string GROUP = '67f8978f-6205-4e95-a20c-e82fa3367f6a';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN_MEMBER);
        $this->assertIsCollectionResponse($response);
    }

    public function testCreateChildNodeDirectlyOnGroup(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPostRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_MEMBER,
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
            self::TOKEN_MEMBER,
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
            self::TOKEN_MEMBER,
            [
                'type' => 'OWNS',
                'start' => self::GROUP,
                'end' => $dataId,
            ]
        );
        $this->assertIsCreatedResponse($response);
    }

    public function testMemberCannotUpdateGroupThroughPatch(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPatchRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_MEMBER,
            [
                'name' => 'Group updated through PATCH by a plain member',
            ]
        );
        $this->assertIsProblemResponse($response, 404);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_MEMBER);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertStringNotContainsString('Group node was updated.', $auditLogEntry);
    }

    public function testMemberCannotUpdateGroupThroughPut(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_MEMBER,
            [
                'name' => 'Group updated through PUT by a plain member',
            ]
        );
        $this->assertIsProblemResponse($response, 404);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_MEMBER);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group', $body['data']['name']);
    }

    public function testMemberCannotDeleteGroup(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::GROUP), self::TOKEN_MEMBER);
        $this->assertIsProblemResponse($response, 404);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_MEMBER);
        $this->assertIsNodeResponse($response, 'Group');
    }

    public function testOwnerCanUpdateGroupThroughPatch(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPatchRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_OWNER,
            [
                'name' => 'Group updated through PATCH by its owner',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_OWNER);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group updated through PATCH by its owner', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'Group node was updated.');
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }

    public function testOwnerCanUpdateGroupThroughPut(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPutRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_OWNER,
            [
                'name' => 'Group updated through PUT by its owner',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_OWNER);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group updated through PUT by its owner', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'Group node was updated.');
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }

    public function testAccessUserCanUpdateGroupThroughPatch(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runPatchRequest(
            sprintf('/%s', self::GROUP),
            self::TOKEN_ACCESS,
            [
                'name' => 'Group updated through PATCH via explicit update access',
            ]
        );
        $this->assertNoContentResponse($response);

        // TOKEN_ACCESS only has HAS_UPDATE_ACCESS/HAS_DELETE_ACCESS, not read access, so verify via the member token
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_MEMBER);
        $this->assertIsNodeResponse($response, 'Group');
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Group updated through PATCH via explicit update access', $body['data']['name']);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'Group node was updated.');
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }

    public function testAccessUserCanDeleteGroup(): void
    {
        $auditLogOffset = $this->getAuditLogSize();

        $response = $this->runDeleteRequest(sprintf('/%s', self::GROUP), self::TOKEN_ACCESS);
        $this->assertIsDeletedResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_ACCESS);
        $this->assertIsProblemResponse($response, 404);

        $auditLogEntry = $this->getAuditLogContentsSince($auditLogOffset);
        $this->assertAuditLogContains($auditLogEntry, 'audit', 'NOTICE', 'Group node was deleted.');
        $this->assertStringContainsString(self::GROUP, $auditLogEntry);
    }
}
