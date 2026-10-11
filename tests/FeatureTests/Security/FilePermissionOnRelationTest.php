<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies the relation with a file of the "security.filePermission" reference dataset scenario: User A owns both
 * nodes the relation connects, User B has no relation to either of them and must be unable to see the relation or
 * its file. Also verifies that a relation's file survives a backup load intact.
 */
class FilePermissionOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN_USER_A = 'secret-token:V8m72O3ovtRU09JrdbJRnh';
    private const string TOKEN_USER_B = 'secret-token:3JMIdZMTqEF27mZHDO9AoA';
    private const string RELATION_ID = '5ed54a8f-b4d5-4e95-a7f3-d23c026c8afe';
    private const string FILE_CONTENT = "File attached to a relation.\nUsed by the security.filePermission scenario of the Ember Nexus reference dataset.\n";

    public function testOwningUserCanAccessRelationAndFile(): void
    {
        $relationResponse = $this->runGetRequest(sprintf('/%s', self::RELATION_ID), self::TOKEN_USER_A);
        $this->assertIsRelationResponse($relationResponse, 'RELATED');
        $body = $this->getBody($relationResponse);
        $this->assertSame(strlen(self::FILE_CONTENT), $body['file']['contentLength']);
        $this->assertSame('txt', $body['file']['extension']);
        $this->assertSame(hash('sha256', self::FILE_CONTENT), $body['file']['hash']['sha256']);

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', self::RELATION_ID), self::TOKEN_USER_A);
        $this->assertIsBinaryStreamResponse($fileResponse, 'text/plain');
        $this->assertSame(self::FILE_CONTENT, (string) $fileResponse->getBody());
    }

    public function testUserWithoutAccessGetsNotFoundForRelationAndFile(): void
    {
        $relationResponse = $this->runGetRequest(sprintf('/%s', self::RELATION_ID), self::TOKEN_USER_B);
        $this->assertIsProblemResponse($relationResponse, 404);

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', self::RELATION_ID), self::TOKEN_USER_B);
        $this->assertIsProblemResponse($fileResponse, 404);
    }
}
