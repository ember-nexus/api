<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', 'ad7e53bd-4506-43d3-8273-08629b2f37d0', 'b2eb6f57-532f-4c53-8337-630352234a41', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', 'fc8d2430-8a52-4795-9d07-b17732f2d238', 'b2eb6f57-532f-4c53-8337-630352234a41', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '9aa8582b-dbed-4dba-9beb-4efe354f68f9', 'b2eb6f57-532f-4c53-8337-630352234a41', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', 'e820b7c7-c943-4a96-b187-21e2b54bc284', 'b2eb6f57-532f-4c53-8337-630352234a41', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', 'fd842314-d833-4fb5-bbe9-b4cf6e84516c', 'b2eb6f57-532f-4c53-8337-630352234a41', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', 'c054503c-af5d-4cc2-8201-f0abc4cc91dc', 'b2eb6f57-532f-4c53-8337-630352234a41', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', 'b5174127-92fe-4e2c-8e1e-c70311e6ffcc', 'b2eb6f57-532f-4c53-8337-630352234a41', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
