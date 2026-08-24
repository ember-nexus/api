<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies the "security.filePermission" reference dataset scenario: User A created and owns a Data element
 * with a file, User B has no relation to it at all and must be unable to see the element or its file.
 */
#[Group('test')]
class FilePermissionTest extends BaseRequestTestCase
{
    private const string TOKEN_USER_A = 'secret-token:V8m72O3ovtRU09JrdbJRnh';
    private const string TOKEN_USER_B = 'secret-token:3JMIdZMTqEF27mZHDO9AoA';
    private const string DATA_ID = '592693f7-b1be-454e-b9f0-ed0416e7df48';

    public function testOwningUserCanAccessElementAndFile(): void
    {
        $elementResponse = $this->runGetRequest(sprintf('/%s', self::DATA_ID), self::TOKEN_USER_A);
        $this->assertIsNodeResponse($elementResponse, 'Data');

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', self::DATA_ID), self::TOKEN_USER_A);
        $this->assertIsBinaryStreamResponse($fileResponse, 'application/octet-stream');
    }

    public function testUserWithoutAccessGetsNotFoundForElementAndFile(): void
    {
        $elementResponse = $this->runGetRequest(sprintf('/%s', self::DATA_ID), self::TOKEN_USER_B);
        $this->assertIsProblemResponse($elementResponse, 404);

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', self::DATA_ID), self::TOKEN_USER_B);
        $this->assertIsProblemResponse($fileResponse, 404);
    }
}
