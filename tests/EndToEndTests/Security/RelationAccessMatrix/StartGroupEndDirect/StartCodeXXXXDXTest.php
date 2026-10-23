<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXDXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', 'a70f9498-e93f-4245-b25a-67bbdbec5e3a', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', 'e64c233a-5242-4019-8090-7442877875ff', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '3e9e653d-5ab8-4304-9ade-b0070c6f1dba', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', 'c18cd054-a0f8-4b4e-99b9-475be56ad27e', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '2b6c2419-b452-4dd9-bae5-d123002a8a46', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '8358aacc-e2b6-4ea4-ad0e-291dfeb02f2e', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', 'dc9e69bc-68c3-4175-bc95-ed0b3637e8d2', 'ee28a434-dd7b-47c2-be1b-9c268a4c30ff', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
