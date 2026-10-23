<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '10309a1e-c4b7-4bc7-a15a-035f271842fb', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '17bfa2b7-ce8d-4957-9cb0-f074f0017495', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '7b4eb9c4-049d-43cd-bb2d-ea9dcb2e7ae0', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', 'b0e06def-21fa-4050-81a4-fcb42fb13f4f', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '1019af39-1e50-4375-827e-0f340efee8ce', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '305dfaf9-a89b-4130-9bbb-4074ff265faa', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '0335d659-206a-4bf3-bb8c-e79bf4c988af', 'c5f98e8a-ec59-408e-80e8-6aaf703450ea', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
