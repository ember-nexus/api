<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies authorization on the `/{id}/file` endpoints (POST, PUT, DELETE, GET):
 * - POST, PUT and DELETE all require UPDATE access on the target element; a user without it is rejected with 404.
 * - GET requires only READ access; a user with READ but not UPDATE access can retrieve the element itself but is
 *   still rejected with 404 when trying to write to its file.
 *
 * Reuses the "security.filePermission" scenario (User A owns a Data element with a file, User B has no relation
 * to it at all) for the UPDATE checks, and the "security.limitedAccess.readAccess" scenario (a user with a direct
 * HAS_READ_ACCESS relation to a Data element it does not own) for the READ-only check.
 */
#[Group('test')]
class FileEndpointAccessControlTest extends BaseRequestTestCase
{
    private const string TOKEN_OWNER = 'secret-token:V8m72O3ovtRU09JrdbJRnh';
    private const string TOKEN_STRANGER = 'secret-token:3JMIdZMTqEF27mZHDO9AoA';
    private const string OWNED_DATA_WITH_FILE_ID = '592693f7-b1be-454e-b9f0-ed0416e7df48';

    private const string TOKEN_READ_ONLY = 'secret-token:S55SWBoCDt99LT95JTIWg0';
    private const string READ_ONLY_DATA_ID = '135a2d64-8c7a-41d5-9f4b-75fef057846a';

    public function testOwnerCanPostFileToNewElement(): void
    {
        $elementId = $this->createElement(self::TOKEN_OWNER);

        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), $file, self::TOKEN_OWNER);
        $this->assertIsCreatedResponse($response, false);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    public function testUserWithoutAccessCannotPostFileToElement(): void
    {
        // the target already has a file, but the access check happens before the "already has a file" check, so
        // a denied user must see 404, not 409
        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', self::OWNED_DATA_WITH_FILE_ID),
            $file,
            self::TOKEN_STRANGER
        );
        $this->assertIsProblemResponse($response, 404);
    }

    public function testOwnerCanPutFileToReplaceExisting(): void
    {
        $elementId = $this->createElement(self::TOKEN_OWNER);

        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $createResponse = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), $file, self::TOKEN_OWNER);
        $this->assertIsCreatedResponse($createResponse, false);

        $replacementFile = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $putResponse = $this->runUploadRequest('PUT', sprintf('/%s/file', $elementId), $replacementFile, self::TOKEN_OWNER);
        $this->assertIsCreatedResponse($putResponse, false);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    public function testUserWithoutAccessCannotPutFileToElement(): void
    {
        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', self::OWNED_DATA_WITH_FILE_ID),
            $file,
            self::TOKEN_STRANGER
        );
        $this->assertIsProblemResponse($response, 404);

        // the file must remain untouched: the owner can still retrieve it afterwards
        $getFileResponse = $this->runGetRequest(sprintf('/%s/file', self::OWNED_DATA_WITH_FILE_ID), self::TOKEN_OWNER);
        $this->assertIsBinaryStreamResponse($getFileResponse, 'image/jpeg');
    }

    public function testOwnerCanDeleteFile(): void
    {
        $elementId = $this->createElement(self::TOKEN_OWNER);

        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $createResponse = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), $file, self::TOKEN_OWNER);
        $this->assertIsCreatedResponse($createResponse, false);

        $deleteResponse = $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN_OWNER);
        $this->assertIsDeletedResponse($deleteResponse);

        $getFileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN_OWNER);
        $this->assertIsProblemResponse($getFileResponse, 404);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    public function testUserWithoutAccessCannotDeleteFile(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::OWNED_DATA_WITH_FILE_ID), self::TOKEN_STRANGER);
        $this->assertIsProblemResponse($response, 404);

        // the file must remain untouched: the owner can still retrieve it afterwards
        $getFileResponse = $this->runGetRequest(sprintf('/%s/file', self::OWNED_DATA_WITH_FILE_ID), self::TOKEN_OWNER);
        $this->assertIsBinaryStreamResponse($getFileResponse, 'image/jpeg');
    }

    public function testUserWithReadOnlyAccessCanReadElementButNotWriteItsFile(): void
    {
        $elementResponse = $this->runGetRequest(sprintf('/%s', self::READ_ONLY_DATA_ID), self::TOKEN_READ_ONLY);
        $this->assertIsNodeResponse($elementResponse, 'Data');

        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $postResponse = $this->runUploadRequest('POST', sprintf('/%s/file', self::READ_ONLY_DATA_ID), $file, self::TOKEN_READ_ONLY);
        $this->assertIsProblemResponse($postResponse, 404);

        $file = \Safe\fopen(__DIR__.'/../Asset/cherry-blossoms.jpg', 'r');
        $putResponse = $this->runUploadRequest('PUT', sprintf('/%s/file', self::READ_ONLY_DATA_ID), $file, self::TOKEN_READ_ONLY);
        $this->assertIsProblemResponse($putResponse, 404);

        $deleteResponse = $this->runDeleteRequest(sprintf('/%s/file', self::READ_ONLY_DATA_ID), self::TOKEN_READ_ONLY);
        $this->assertIsProblemResponse($deleteResponse, 404);
    }

    private function createElement(string $token): string
    {
        $response = $this->runPostRequest(
            '/',
            $token,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-access-control-test',
                ],
            ]
        );

        return $this->getUuidFromLocation($response);
    }
}
