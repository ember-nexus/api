<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '21a36332-4f1c-4ddb-a30d-e093ec31f91b', '75034fd9-d756-4ced-9336-03a3efa2384f', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', 'b3dcf5be-4572-4145-b4a6-1f28641187df', '75034fd9-d756-4ced-9336-03a3efa2384f', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '4480f3e8-655f-47e4-9b12-6c9563c15fe8', '75034fd9-d756-4ced-9336-03a3efa2384f', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '9a2b9d94-39be-4af0-a7da-4ccf67bc55e8', '75034fd9-d756-4ced-9336-03a3efa2384f', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '26c94c62-29d8-439f-8e0f-2d0c3cebb90d', '75034fd9-d756-4ced-9336-03a3efa2384f', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '5db4fe47-b9a0-4bf6-b72f-f0a05828252b', '75034fd9-d756-4ced-9336-03a3efa2384f', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', 'b6d2525c-a8e8-4fbc-a51a-afb57f26405c', '75034fd9-d756-4ced-9336-03a3efa2384f', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
