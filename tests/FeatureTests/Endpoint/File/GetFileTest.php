<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class GetFileTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string NODE_WITH_FILE_UUID = '0fdd52ba-55da-430c-b015-3277a231e895';
    private const string NODE_WITHOUT_FILE_UUID = 'b7121a49-8f17-4858-b988-4bd837a5f1fe';

    public function testGetFileOnNodeWithFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::NODE_WITH_FILE_UUID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'application/octet-stream');
    }

    public function testGetFileOnNodeWithoutFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::NODE_WITHOUT_FILE_UUID), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @todo
     */
    public function testGetFileOnRelationWithFile(): void
    {
        $this->markTestSkipped();
    }

    /**
     * @todo
     */
    public function testGetFileOnRelationWithoutFile(): void
    {
        $this->markTestSkipped();
    }
}
