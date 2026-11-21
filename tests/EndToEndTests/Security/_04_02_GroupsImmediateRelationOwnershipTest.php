<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _04_02_GroupsImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:UlPeqBZJqgoaJFLvBKC26';

    private const string GROUP_1 = '02IiNr0GOoKiPMMa1MEQA6qOn2DFgfU1pkFAm';
    private const string GROUP_2 = '02IiNr0GOoKiPMM3otynWAlO5x6xw3JDhhwwy';
    private const string GROUP_3 = '02IiNr0GOoKiPMMFexQda15nXEUluOHLNrHta';
    private const string DATA_1 = '02IiNr0GOoKiPMMnvlF5JDruJ41zLpQPasMO4';
    private const string DATA_2 = '02IiNr0GOoKiPMLeYnIbDtjBINHFBfGH6auuM';
    private const string IS_IN_GROUP_1 = '02IiNr0GOoKiPMLrJREqRJYzlFmlHlcEvpWJw';
    private const string IS_IN_GROUP_2 = '02IiNr0GOoKiPMMfnwKjRoLhTeeq91DbpnYKE';
    private const string IS_IN_GROUP_3 = '02IiNr0GOoKiPMMYCC73HN63tw4uoOJLLHRqw';
    private const string OWNS_DATA_1 = '02IiNr0GOoKiPMN6QwoMU0W1eg0XVEACY70VC';
    private const string OWNS_DATA_2 = '02IiNr0GOoKiPMLWWYrNP4s5FNgVnsnN430bG';
    private const string OWNS_TOKEN = '02IiNr0GOoKiPMLSKGE4Jk7WLUzDXWXlqdRK5';
    private const string RELATION = '02IiNr0GOoKiPMMGdPUNEfH9QAY6yHj29lxeU';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_3), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
