<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '9c55b1af-17b5-4a69-852e-acc5a79babca', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', 'c6cff502-d27f-4ef3-80d9-1415ea82df5f', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '45b0c26b-d7ab-4a49-9566-a5767420137f', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', 'c1084955-ca23-4e58-84e1-25fee58f9dd5', '54e6ee03-137f-4711-8f08-c6af4bd53f57', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '1a8d20b3-4d1a-4d2c-b9bf-b5ac6d3c3142', '54e6ee03-137f-4711-8f08-c6af4bd53f57', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '36e5fb03-db33-42da-a86c-a32d1a6b4a32', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', 'f3017a0c-f12d-443e-a463-4dc1cec03b0f', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
