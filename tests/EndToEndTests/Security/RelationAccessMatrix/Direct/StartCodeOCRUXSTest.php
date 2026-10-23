<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', 'b4884252-8eac-417b-b905-495bd9f2202b', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '941ae5c7-051f-4f31-9713-e37831b0bc1b', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '2369d88c-4d8a-4862-a096-a065b381c858', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', 'f178db87-f3c7-44f4-985d-205b0f2d97ba', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', 'd3f115f2-8af3-41e0-a517-52d92b42324f', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', 'bf228890-bdc4-45e5-b031-a4b9614d1bd9', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', 'ee8ad5b9-c204-48ab-bb4f-9a84c1f19653', 'd24b33e0-2b8e-410d-87ab-e28acd6c85e0', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
