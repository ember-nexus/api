<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '60d6d393-150f-4e0d-9128-812735926b07', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', 'f5a38d63-f4eb-408e-aa6b-7514b5d2b950', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '2476299c-79b4-49fe-a776-d7da4dba3aef', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', 'd02320ae-7755-4c08-9fb7-3bc533063a21', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '55d9bd0c-ed24-4091-893b-d0ba128a8682', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '0e06e340-9e68-4218-8f8e-523871afd719', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', 'fd6ef6b3-8521-4bb6-833c-e6d563b1cf4e', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
