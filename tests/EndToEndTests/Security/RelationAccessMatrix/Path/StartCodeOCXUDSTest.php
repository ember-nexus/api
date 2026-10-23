<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', 'b780d953-dbe2-4c49-896b-5ae63d99c30d', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '3182d34a-e500-4886-8055-43620a5dee81', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', 'b9301b21-7377-4727-9b32-2ccaf018ef9a', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', 'c88e2036-2121-46dc-ada9-5cd8d5808048', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '6f93d2f1-bcf1-42af-8b9c-75ba61cbd068', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '344bf2ec-c019-4f83-bdce-30c0838a9030', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '16feb737-802f-4dab-b88d-803538f18103'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', 'fb16d481-5486-4698-954c-8a1555cfd0a3', 'f3f8a0be-dee2-4a43-9a22-01bc0b1cee19', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
