<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '3083b0c7-774e-462f-9996-6ae94e0d6846', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '65b1cbe0-b9ad-4fdb-9c5a-2c653337d9a2', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '9a8784d5-a1a7-4310-ab52-d48857715cac', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '7ba84a54-e05b-4565-a360-a8916432d64a', '446f11b9-c9f9-438c-a822-c84dfce5ee98', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', 'fa8c4055-71d7-4d3f-99fa-c542ec97d1bc', '446f11b9-c9f9-438c-a822-c84dfce5ee98', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '6fbbc3cf-d9c5-4227-b262-91c585dcbac8', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '112766ef-7df2-4796-bbe4-508eaed60766', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
