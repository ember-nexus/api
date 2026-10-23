<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', 'c6105017-4d16-489e-b58e-4fe8d8cfbe57', '216fae63-4d69-4681-8540-ea2e0011442b', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '0f90318d-6093-4a44-aa15-b390b4cea319', '216fae63-4d69-4681-8540-ea2e0011442b', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', 'b190d231-3f63-475e-9ad2-831c1adbcb50', '216fae63-4d69-4681-8540-ea2e0011442b', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', 'a38df302-2a59-48d2-accc-aa815eb17203', '216fae63-4d69-4681-8540-ea2e0011442b', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', 'c7ef64e7-996f-4bde-83b3-a06826073799', '216fae63-4d69-4681-8540-ea2e0011442b', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', 'da97d5b9-1375-444b-8172-cb31a9403748', '216fae63-4d69-4681-8540-ea2e0011442b', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', 'f8f285b6-3aec-417a-97b4-11fe40a64da6', '216fae63-4d69-4681-8540-ea2e0011442b', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
