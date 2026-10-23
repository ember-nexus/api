<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', 'debe792d-f4ac-4415-b185-4d871331da36', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '3cf27820-e581-4dce-b614-c4a9e6ed8f5c', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', 'aaa4efd7-0e08-4cc7-9cda-c7eb6ff8f24d', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', 'ce473500-fc07-47cd-8ab2-9773c73d17b1', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '6ff90da4-c563-4321-a989-8efc072f3a75', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '8652666a-5f1f-4e8f-812d-28850503cea6', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '178aff57-0433-4860-9197-f0fcc8e18d51', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
