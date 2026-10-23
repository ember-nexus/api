<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
{
    #[DataProvider('pairs')]
    public function testRelationAccess(string $startCode, string $endCode, string $relationId, string $startId, string $endId): void
    {
        $this->assertRelationAccess($startCode, $endCode, $relationId, $startId, $endId);
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function pairs(): array
    {
        return [
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '7aa40773-547b-41bc-9a10-c9a8b2dccdfe', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '15d31a2c-63f2-4485-944f-c1080e54eab1', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '5354d50c-955b-4486-89cc-2c9d423c44b7', '8db6b8b0-9fa0-4fe8-a437-86961849f011', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', 'a04a40e4-b529-430f-b28e-ba25c138a5cb', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '2531b9a9-1652-4581-835a-b74934a03fa6', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '40e066fa-9c49-40e9-8ccd-76f4c93c10dd', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '16feb737-802f-4dab-b88d-803538f18103'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '5735c5ce-23aa-4887-8697-d472ce62d53f', '8db6b8b0-9fa0-4fe8-a437-86961849f011', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
