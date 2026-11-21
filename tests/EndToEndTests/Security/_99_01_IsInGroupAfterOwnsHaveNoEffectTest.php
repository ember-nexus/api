<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _99_01_IsInGroupAfterOwnsHaveNoEffectTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:AZuaUFmZ6YB2g14CO1qAlM';

    private const string GROUP_1 = '02IiNr0GOoKiPMMOsloJYwn4AdGz8MffzNczp';
    private const string GROUP_2 = '02IiNr0GOoKiPMMCQy1VcE1eJEjd9KA2NlmGZ';
    private const string DATA_1 = '02IiNr0GOoKiPMN5aDD4UcJuUs8RwxirhLsNb';
    private const string DATA_2 = '02IiNr0GOoKiPMMIHJXOuDJMBSYY8P1HmTtwq';
    private const string OWNS_TOKEN = '02IiNr0GOoKiPMLmefdukVmSF7I2QMIiqWZph';
    private const string IS_IN_GROUP_1 = '02IiNr0GOoKiPMLKNjknDdUygWyLoMa23ruW0';
    private const string IS_IN_GROUP_2 = '02IiNr0GOoKiPMLDJs4IoZF5rDn7Jf3576m1q';
    private const string OWNS_DATA_1 = '02IiNr0GOoKiPMMc3pbhqSfLeABPHt5KqBiOO';
    private const string OWNS_DATA_2 = '02IiNr0GOoKiPMMyr6SjKgfA9KMV9tWqWt8Au';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
    }

    public function testGetForbiddenNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetForbiddenRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
