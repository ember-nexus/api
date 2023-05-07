<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_02_NoRelevantConnectionTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:1KLilQkUoSgY4BhbCQimec';

    public const DATA = '6a8a8afb-2a07-4fa2-b467-0afa359a4b7e';
    public const RELATIONSHIP = 'aac81a63-2d2e-40ab-b0bb-471f30a2b119';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetForbiddenRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATIONSHIP), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
