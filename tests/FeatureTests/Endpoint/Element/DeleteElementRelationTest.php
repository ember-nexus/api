<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteElementRelationTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:DZnGleUhhBS5CY2T3JUkFY';
    public const RELATION = '15491c7e-49b2-4c30-8762-2cb78b8503ea';

    public function testDeleteRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
        $response = $this->runDeleteRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsDeletedResponse($response);
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
