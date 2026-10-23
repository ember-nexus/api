<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '8074a12f-219a-42e5-85cb-64a17ec1135e', '00913f2f-e8c6-44f7-b55f-10a594f84127', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '88217b87-8762-449d-b27e-2bb0b0172b01', '00913f2f-e8c6-44f7-b55f-10a594f84127', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', 'b9572fc9-61c5-4272-9817-bd1304b4d624', '00913f2f-e8c6-44f7-b55f-10a594f84127', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '9dff3092-cebc-4bf5-92fa-f96e0af44e91', '00913f2f-e8c6-44f7-b55f-10a594f84127', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '56c04221-b4a1-427f-83bb-9ab16ca83be6', '00913f2f-e8c6-44f7-b55f-10a594f84127', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '32038ecb-78d9-4273-9dd9-cf4a6dac12b4', '00913f2f-e8c6-44f7-b55f-10a594f84127', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '07609b58-7287-48dd-a5df-c3d11fb16a52', '00913f2f-e8c6-44f7-b55f-10a594f84127', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
