<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '7534d165-b24c-4995-843b-0cf95c4ad6ed', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', 'f8745f3c-f816-43a7-bd73-addaa7c05083', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', 'd6464ceb-0df9-4efa-92af-b2b67d256b8f', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '761ea9ee-1f3f-466b-8c95-b77efba6007c', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', 'e6f8eedf-e0d5-4d9b-8caa-74bf4e27ec62', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '8705c716-f3ec-4886-b8f2-0d6f506cf9b1', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', 'e4bcbd84-b1c6-4de3-9b82-2ebfd4680dc0', '70eaca29-b0f3-4bfa-b533-57151ea3a5a2', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
