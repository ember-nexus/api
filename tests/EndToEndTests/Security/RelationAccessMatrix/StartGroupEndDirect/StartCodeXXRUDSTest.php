<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '478a7bb9-4391-439f-82ad-99e13f10b04d', '67c06201-8fdb-4c03-b672-ded0c7ded693', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '7852e012-0691-428e-90c5-d96506396bcf', '67c06201-8fdb-4c03-b672-ded0c7ded693', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '684d5585-25db-4235-8c2a-b7e97d216ed5', '67c06201-8fdb-4c03-b672-ded0c7ded693', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '75ceb392-25bc-4fcc-821c-68aa2f10157e', '67c06201-8fdb-4c03-b672-ded0c7ded693', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '6b2df718-4192-4c13-a0e3-f9499bbff750', '67c06201-8fdb-4c03-b672-ded0c7ded693', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '4c49d540-c365-4d72-974f-1d2801e73543', '67c06201-8fdb-4c03-b672-ded0c7ded693', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '5b7e9d15-679b-48ca-ab58-b2af6846c84b', '67c06201-8fdb-4c03-b672-ded0c7ded693', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
