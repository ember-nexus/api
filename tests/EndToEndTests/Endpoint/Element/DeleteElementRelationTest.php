<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class DeleteElementRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:DZnGleUhhBS5CY2T3JUkFY';
    private const string RELATION = '02IiNr0GOoKiPMLHJ83tP5dQpiHIuggNCSRiN';

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
