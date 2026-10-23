<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '6689a2b4-30e6-4835-b97e-e040915fa1f8', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '48ba1164-3a97-44ea-b5d7-b597ecb6ce28', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', 'b0d56c22-a990-48be-b78c-d2ff09254eca', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '05b64576-de8b-49e1-8dcd-16e7e31768ae', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', 'f31f1610-abf0-4707-9786-52da5859500c', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '42c8e8d3-0e0e-4615-ba9f-e6dd7c7c0891', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '16feb737-802f-4dab-b88d-803538f18103'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '715e5cc9-c7a6-49a4-82af-f975c474112d', 'd896721e-a462-47c1-8b2d-b86c1b986fa2', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
