<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '3c2fe799-e752-4b0b-a9eb-b21087801ab2', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '901472fe-b27e-4473-81a2-16b490706ec7', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '242cc137-4049-4d3f-85a7-2c00a9a38137', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', 'cd6b0f30-5253-451c-9c06-b39bd64cb2ba', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', 'f72b23ad-0152-413d-9c97-c7c677d38c14', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', 'af7cfc0f-204d-4f62-927f-a4f32bfba962', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', 'be292eac-a01d-45b3-b5fc-2f7d2f17f748', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
