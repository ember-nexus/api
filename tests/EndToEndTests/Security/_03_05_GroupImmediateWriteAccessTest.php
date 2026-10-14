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
        $containerLogOffset = $this->getContainerLogSize();

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
        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringNotContainsString(self::GROUP, $auditLogLines);
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
        $containerLogOffset = $this->getContainerLogSize();

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

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringNotContainsString('Group node was updated.', $auditLogLines);
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
        $containerLogOffset = $this->getContainerLogSize();

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

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'Group node was updated.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString(self::GROUP, $auditLogLines);
    }

    public function testOwnerCanUpdateGroupThroughPut(): void
    {
        $containerLogOffset = $this->getContainerLogSize();

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

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'Group node was updated.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString(self::GROUP, $auditLogLines);
    }

    public function testAccessUserCanUpdateGroupThroughPatch(): void
    {
        $containerLogOffset = $this->getContainerLogSize();

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

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'Group node was updated.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString(self::GROUP, $auditLogLines);
    }

    public function testAccessUserCanDeleteGroup(): void
    {
        $containerLogOffset = $this->getContainerLogSize();

        $response = $this->runDeleteRequest(sprintf('/%s', self::GROUP), self::TOKEN_ACCESS);
        $this->assertIsDeletedResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN_ACCESS);
        $this->assertIsProblemResponse($response, 404);

        $containerLogContent = $this->getContainerLogContentsSince($containerLogOffset);
        $this->assertAuditLogContains($containerLogContent, 'audit', 'NOTICE', 'Group node was deleted.');
        $auditLogLines = $this->extractChannelLogLines($containerLogContent, 'audit');
        $this->assertStringContainsString(self::GROUP, $auditLogLines);
    }
}
