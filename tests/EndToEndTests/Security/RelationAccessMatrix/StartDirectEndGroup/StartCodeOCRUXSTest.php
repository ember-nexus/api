<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', 'a937e431-1949-4ed8-8518-2f5ebd9f672d', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', 'e73a245d-8bfc-4dda-b307-f984b978d373', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '828f0396-c58c-4094-beba-577d780e2360', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '3623f0e1-427e-4bfb-a683-0d1cc6d8e33c', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', 'e57ee0de-19f1-4bbe-bdec-77632d678b74', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '84f4e8bd-ef8e-4569-a9a9-a8dfe5084b06', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', 'c1b16075-4648-4976-839d-b26881f2cb32', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
