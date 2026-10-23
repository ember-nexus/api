<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '45962b7d-ec34-40c7-88c2-c17dff8a132f', '2a46c844-be29-4535-8373-6b299cb47fdf', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '42f0f310-240f-4717-8236-6fd9d9856b96', '2a46c844-be29-4535-8373-6b299cb47fdf', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '132d85ac-c04a-4ab7-bf8f-6de30277524f', '2a46c844-be29-4535-8373-6b299cb47fdf', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '837f09cc-b4e3-4242-a17e-e4e01be466d3', '2a46c844-be29-4535-8373-6b299cb47fdf', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '506ab28f-062a-4905-9ced-a8b7b4d04bc2', '2a46c844-be29-4535-8373-6b299cb47fdf', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '2c0838a9-977e-43b4-970f-8acf8f1e88e0', '2a46c844-be29-4535-8373-6b299cb47fdf', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '6d8298f4-6a44-48a6-932a-c06596900532', '2a46c844-be29-4535-8373-6b299cb47fdf', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
