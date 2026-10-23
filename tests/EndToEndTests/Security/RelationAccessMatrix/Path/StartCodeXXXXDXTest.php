<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXDXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', 'd791c497-e15d-45fc-82b2-d7f654a33756', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', 'a6bb5dd5-f00b-4133-a319-00bde1bf53ee', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', 'c54bfdd6-5ee3-44b8-82d2-ea2ec2b2f9dc', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '11e444e3-5370-40f5-8925-ac2165d98831', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', 'bb7057e3-e2ac-4a13-aa35-804d2a69e3cd', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '695fddd3-9c4e-4d99-945f-fd611ec23126', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '16feb737-802f-4dab-b88d-803538f18103'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '013f902c-bfa5-406a-8220-28e2dccddf78', 'cc4f1358-e1b0-40aa-b9ca-ad8567a3d1fb', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
