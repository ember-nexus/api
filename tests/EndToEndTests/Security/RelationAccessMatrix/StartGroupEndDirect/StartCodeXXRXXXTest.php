<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '0edee4fa-5f46-4efa-9f87-892726193ab5', '8051b81a-525c-423d-a7b8-d150679b9f96', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '872a35b8-6469-4fb8-bcf7-4dd79eaff1f9', '8051b81a-525c-423d-a7b8-d150679b9f96', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '60e1e8b2-e6db-40a4-bf2a-1b5fd4ba166a', '8051b81a-525c-423d-a7b8-d150679b9f96', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', 'f05bc822-ad15-47a3-9236-649507f8dd7e', '8051b81a-525c-423d-a7b8-d150679b9f96', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', 'f6bd7b52-ed54-47c5-b7c3-b05a4b8b0ba5', '8051b81a-525c-423d-a7b8-d150679b9f96', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', 'f484056d-6802-471c-950a-25b7b1686422', '8051b81a-525c-423d-a7b8-d150679b9f96', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', 'e35aa0db-24d7-45eb-87f5-4d86be83663e', '8051b81a-525c-423d-a7b8-d150679b9f96', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
