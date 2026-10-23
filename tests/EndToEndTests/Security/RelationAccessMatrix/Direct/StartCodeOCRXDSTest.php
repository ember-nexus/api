<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', 'e1e6e1b3-2e9a-454e-a18b-896323f49b91', '889a9717-8dbf-4384-b146-7afb5b399705', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', 'da6389cf-c84d-4193-b8e5-26621f29237d', '889a9717-8dbf-4384-b146-7afb5b399705', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', 'c1b13936-8500-4604-a2e2-240c29c6d263', '889a9717-8dbf-4384-b146-7afb5b399705', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '7c8e9f14-6525-4e42-94c2-0a4528012403', '889a9717-8dbf-4384-b146-7afb5b399705', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '1e9e3e2e-b4a0-49ba-b3ba-b3f731c2ed06', '889a9717-8dbf-4384-b146-7afb5b399705', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '4762de3f-8ee4-4f07-9410-79b6e0d554e5', '889a9717-8dbf-4384-b146-7afb5b399705', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '8d220901-aee7-4e49-a99f-f9471b1a8eb9', '889a9717-8dbf-4384-b146-7afb5b399705', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
