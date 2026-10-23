<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '8fae2d6d-3ae0-4c37-b878-a819eabb93f9', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '42b320cc-bebf-4305-8bf4-de639e5cc971', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', 'f3f214b7-eb1f-405a-b217-b9a8524403d9', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '950b050a-6b06-4251-a95c-456c0dd60377', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', 'e3e09fe2-a40e-4630-ae6c-fe675787cfdd', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', 'ec7cad16-3fa8-4676-a53c-0e2da11bf603', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', 'e44e20c0-1d10-4449-b445-affcca313342', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
