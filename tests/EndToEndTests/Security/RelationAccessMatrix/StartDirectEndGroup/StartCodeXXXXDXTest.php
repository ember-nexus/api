<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '0d4f0c80-3a4b-4fdb-8bb3-daf3d44b0e08', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', 'e17387ec-96ce-42c3-8c70-5cca0daa92fc', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '3eb92d99-b882-44af-b7fa-e145c34135f7', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '378afa01-680f-48fc-a2dc-03b42876f515', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '4654e323-7cf2-4636-913d-8c1dbb920bff', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '52711100-f847-4a66-bad9-a35dc77cd3f7', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', 'c76f5be4-2c8b-49ff-b0eb-0f42fe36e962', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
