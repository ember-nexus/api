<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '4542a6d0-2b8c-45b7-a84a-69533e3cbe79', '31f5a17c-5d4c-4586-be36-79749d07bb6f', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '1a5c6501-5d3e-4cf2-9785-ed1fda3f8b14', '31f5a17c-5d4c-4586-be36-79749d07bb6f', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '89a180cd-a25b-4ca2-8e0a-f9d0cfca5107', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '4205c957-1f7f-4b42-8fa2-a717e3747c49', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '8df84380-e579-4dd6-9ad8-8a2b7ad56233', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', 'd703a9d7-48c1-42d6-a3b3-09d5e0c35e67', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', 'f29270fd-e28a-46aa-a5fc-44698df5206d', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
