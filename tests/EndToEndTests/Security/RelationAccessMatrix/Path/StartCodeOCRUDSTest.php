<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', 'f7ba3c79-44e9-444e-afd6-42a3066444e2', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '1cf674fb-d91b-4c70-a12d-c8e8c1c81cba', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', 'b57995ce-7544-4655-80da-c70bf562ab96', 'da735a77-cdcf-4517-bfc4-78f46422adc4', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', 'f849f37a-a9cf-4e9b-bf5a-d32affe27dd0', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '9a6bc1aa-aa8e-4ead-8efa-f8512471d43e', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '038795c2-9e32-4cd9-b992-08f5f75438e2', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '16feb737-802f-4dab-b88d-803538f18103'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', 'ab9b975c-668d-4688-8c4e-72e3ad332714', 'da735a77-cdcf-4517-bfc4-78f46422adc4', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
