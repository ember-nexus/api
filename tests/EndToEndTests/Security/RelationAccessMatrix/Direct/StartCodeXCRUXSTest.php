<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', 'c7ee624f-685f-415e-a0fd-cbaa2328673d', 'e196e9ab-9031-40cc-bde5-270124941df4', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', 'a30accd4-1791-4475-b582-5ac369ce8c9b', 'e196e9ab-9031-40cc-bde5-270124941df4', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '924f652a-b1ca-4bd4-8ca9-f717236b39e1', 'e196e9ab-9031-40cc-bde5-270124941df4', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', 'ebb88dac-e506-405d-b466-5af9aebaae6f', 'e196e9ab-9031-40cc-bde5-270124941df4', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', 'b967cd8d-251d-499b-8956-0c625e621c60', 'e196e9ab-9031-40cc-bde5-270124941df4', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '2997c1be-8c4f-481d-9ec2-3b747a3fbb76', 'e196e9ab-9031-40cc-bde5-270124941df4', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '5b171bd8-e2fd-4f4a-86c4-01229b6ad1f8', 'e196e9ab-9031-40cc-bde5-270124941df4', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
