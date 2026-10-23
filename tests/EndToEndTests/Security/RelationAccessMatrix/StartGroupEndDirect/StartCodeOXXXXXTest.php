<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', 'c3d39e7d-fd2b-494a-8526-2a991f625489', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', 'e02cd76b-df35-4394-885b-7c0970995b79', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', 'ad1be62d-df22-4821-b113-0ce789989ce3', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '0b9fbe7b-c131-4947-858c-bbede38ac489', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', 'ff546e32-a722-422b-b78b-e351ad47e09f', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '5f16ae48-b500-4638-b717-298f819286af', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '86f2f5d6-056e-4fd4-bd03-60bfea02bb47', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
