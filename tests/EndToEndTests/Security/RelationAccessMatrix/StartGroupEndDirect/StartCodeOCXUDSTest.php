<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', 'a5d912bd-b678-4cb2-94e0-0b46d1c915d1', '0bd476e6-f235-4905-8308-05a06cd4996c', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '1982ee8a-162e-4c9a-9c13-0a7e5b5fd62e', '0bd476e6-f235-4905-8308-05a06cd4996c', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '0cc58807-f0ee-4e02-9ee6-ccf735c30d71', '0bd476e6-f235-4905-8308-05a06cd4996c', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '181c0dbc-04d7-40e1-9f58-3ba5ca12f686', '0bd476e6-f235-4905-8308-05a06cd4996c', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '78f080d2-b5b1-46e6-b760-9ed572a837e5', '0bd476e6-f235-4905-8308-05a06cd4996c', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '6d59da89-2dad-4ef7-bf33-9451cfe57e60', '0bd476e6-f235-4905-8308-05a06cd4996c', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '7ec79aeb-e772-4596-9402-dbdaa025ca85', '0bd476e6-f235-4905-8308-05a06cd4996c', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
