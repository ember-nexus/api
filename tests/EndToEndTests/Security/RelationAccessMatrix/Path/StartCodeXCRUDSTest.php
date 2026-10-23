<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '35e29531-b92c-4690-9a70-cbf6624f28ab', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', 'ea2bef4a-ea0c-4809-9919-7bb65100e831', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', 'da0831db-e692-45bf-9739-e96de39662c9', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '08ee6583-6fb5-4ce6-b183-0953d15b9d3e', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '2db33bae-b7f4-4fb3-b833-3f8a99cf6edd', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '3110ea5d-f98a-4014-b08e-2cd2419ec0ab', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '16feb737-802f-4dab-b88d-803538f18103'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '84478aa1-f063-4cfc-a853-d6f2a7f9a3ef', 'fe0b87ea-6d52-4c70-9c71-03e66e6eaa87', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
