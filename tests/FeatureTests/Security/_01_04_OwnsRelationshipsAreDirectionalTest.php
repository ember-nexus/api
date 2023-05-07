<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_04_OwnsRelationshipsAreDirectionalTest extends BaseRequestTestCase
{
    public const TOKEN_1 = 'secret-token:CqDtAdaXFchFHI4BAlaHZC';
    public const TOKEN_2 = 'secret-token:BHaj5TtWZU7tJgEobfYvui';

    public const DATA_1 = '3e9787bd-7db6-4294-ba8e-ee6f50d2730f';
    public const DATA_2 = '8beca827-9319-493d-a28c-6b1358c2c135';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_2);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }
}
