<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', 'ae187603-4f2b-47d2-966d-1041f3537574', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '1e229574-cafd-46f4-8df1-953ef81075ae', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', 'db61303f-920b-4c99-9fa3-9ff5ed9ed53d', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '64226fcb-657d-4927-aefb-d38265216838', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '2682e67f-bd37-4f9f-a171-995195b3f705', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '79d4ec9b-f20e-434a-8e04-84fb141d0d43', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '3bd55742-60d0-4f7f-89cb-adc3aaee210b', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
