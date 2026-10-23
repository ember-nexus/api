<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '5e2dfe60-2e65-4b49-a3dc-bef4ece7f13e', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', 'de6f8be2-260d-4824-89ea-8b6ea7a882ee', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', 'f95c4b23-5918-4dea-96cb-919574b9eb54', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '8eaa2244-9be7-4b84-b322-cd186bc3e9be', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '67ae8a11-f0ed-4046-abac-3083a2d45b10', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '9eaf96af-d6ac-4e86-af54-4ca558997f2e', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '0d2f81f8-cc2c-4836-97fd-9791b7515ffa', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
