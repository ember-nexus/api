<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', 'ae6c139d-7bf6-4a6c-9d2e-2f107230919f', '5e25c1ab-899b-455e-8101-36cb709c591b', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', 'cedc69c3-e6de-4093-8d11-7e307b2eb2d1', '5e25c1ab-899b-455e-8101-36cb709c591b', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', 'f323f350-d148-48c2-b7f0-d9ad77ec8cb1', '5e25c1ab-899b-455e-8101-36cb709c591b', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '93d99083-4e48-44f8-a8fc-37ec87d051fb', '5e25c1ab-899b-455e-8101-36cb709c591b', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '25ecea4d-33a2-4bdd-b491-4bf2d9d9aa0f', '5e25c1ab-899b-455e-8101-36cb709c591b', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '2a3cc3fc-7318-4210-8a98-b30c1c78929b', '5e25c1ab-899b-455e-8101-36cb709c591b', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', 'ecbfbf32-12ba-46e3-96da-df1d952e1cf0', '5e25c1ab-899b-455e-8101-36cb709c591b', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
