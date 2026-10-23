<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '00ce7b9d-526a-45e7-9e16-e83d545f56e6', 'b2eb6f57-532f-4c53-8337-630352234a41', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '3a485999-b502-42fb-8276-1d4aa7983ba9', 'b2eb6f57-532f-4c53-8337-630352234a41', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '2324e7b5-28ac-4614-8039-670f0dc6a564', 'b2eb6f57-532f-4c53-8337-630352234a41', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '760ca0e9-104f-4254-8717-6191df0cc177', 'b2eb6f57-532f-4c53-8337-630352234a41', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '7a2f3dae-38c4-409e-b03b-774937743b20', 'b2eb6f57-532f-4c53-8337-630352234a41', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '462ca51e-8a28-49ae-b284-c27f116f28bb', 'b2eb6f57-532f-4c53-8337-630352234a41', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', 'bf6a982c-32d5-4d64-ba1b-7fefbbfa443d', 'b2eb6f57-532f-4c53-8337-630352234a41', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
