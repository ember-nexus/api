<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '65f6b731-e9b1-4869-b5e6-20aeff1347af', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '8e3191bb-6554-4a2f-bf2e-3de3578a5189', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', 'd971e01c-3fa9-4f5e-9124-2c7637e4acb3', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '2b091aa4-96e2-4fff-aa33-6b5670cce01d', '31f5a17c-5d4c-4586-be36-79749d07bb6f', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', 'c724440c-cf9a-46ea-9116-e6394720ccfb', '31f5a17c-5d4c-4586-be36-79749d07bb6f', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', 'db91d62b-3663-42e4-9790-ab662d2ed8a9', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', 'f189732e-37aa-4aad-8368-563eb815919c', '31f5a17c-5d4c-4586-be36-79749d07bb6f', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
