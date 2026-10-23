<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '6a1ab73d-1f7f-48fb-aec0-7afed158b591', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '1af11a0e-5e46-4325-92fa-d0b0c8e11b40', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '3cd1a8a8-e1a5-482e-977a-9f13c2f1d383', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '899b87b8-a2e1-43d3-bf50-26dc1d641f39', '2bf481aa-83bd-4909-aede-c91d9a5a698b', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '551199bb-4fe5-44bb-a960-f5683b804134', '2bf481aa-83bd-4909-aede-c91d9a5a698b', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', 'c0dffb77-9312-442d-9029-03bc429f7da3', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '786a72ef-55d6-406b-b9d8-068d868518ea', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
