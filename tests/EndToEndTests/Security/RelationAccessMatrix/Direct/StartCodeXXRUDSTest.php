<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '5f2cd0e7-cfd8-4ad5-b6bf-551977759014', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', 'f0060d6d-85a7-49d8-a151-92a6cf61ee9b', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '1e9165a0-aa59-42d4-9b58-887cbe20989a', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', 'c82d60af-386f-4af2-84f5-e0087408197d', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '9971fef1-b7db-4d3b-9ce0-a537bfa53139', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '1d93c8bc-961b-42db-b23d-cb6b2b50d199', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', 'a320c66a-a511-41bd-a049-de3b395081e4', '73a76ff3-8edb-41e7-b914-4e58cdd9a755', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
