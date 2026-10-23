<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '618df017-e152-47ee-99e0-f1a0ba29811f', '75034fd9-d756-4ced-9336-03a3efa2384f', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '6dbabfe3-5dac-43c5-82c2-496c1492f1fe', '75034fd9-d756-4ced-9336-03a3efa2384f', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', 'a456ff8a-8429-4467-acb3-eb76fb26a429', '75034fd9-d756-4ced-9336-03a3efa2384f', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '3030c13c-4681-43a3-9e22-5876e822f496', '75034fd9-d756-4ced-9336-03a3efa2384f', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', 'd0435084-12c1-47d0-a532-22a4ade86349', '75034fd9-d756-4ced-9336-03a3efa2384f', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', 'a16b11c1-ba16-4b95-afcb-063f778e9213', '75034fd9-d756-4ced-9336-03a3efa2384f', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', 'fff905f7-18de-453b-954f-cae52cde29d5', '75034fd9-d756-4ced-9336-03a3efa2384f', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
