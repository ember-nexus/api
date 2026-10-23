<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', 'f04ececd-227a-4830-b2dd-e1e78cd84200', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '9501f6a3-95d0-4ca9-a1fa-0597ae3d41fa', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '89c9ef38-b9c5-437a-bce3-900dfdbbf5e6', 'b3312cb5-57ed-4360-a945-3603b32e03a7', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', 'dd6ae509-f010-41fb-9c13-e998d4bd1efc', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', 'bdb07405-f805-4193-b6df-f49c873c171c', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '1e2fde81-b50e-4000-9981-d72cfafb7102', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '16feb737-802f-4dab-b88d-803538f18103'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '597220b2-1226-4bdc-8b51-348cfc816d08', 'b3312cb5-57ed-4360-a945-3603b32e03a7', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
