<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '862c3771-5ea1-44a7-96d8-1654b3a519f7', 'abae95f4-6a73-4075-945d-30e337f17089', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '561dbabe-0fe1-4077-9457-5bce98e7d55c', 'abae95f4-6a73-4075-945d-30e337f17089', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '42a352f1-8f6e-4c4d-8247-88a435c62145', 'abae95f4-6a73-4075-945d-30e337f17089', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '972f26df-99cf-4514-8dea-bfd76565b8ba', 'abae95f4-6a73-4075-945d-30e337f17089', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '14c25764-fe13-492e-aedb-9962aa50f65d', 'abae95f4-6a73-4075-945d-30e337f17089', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '76e24f5e-777e-4bfa-bc62-95e58414153c', 'abae95f4-6a73-4075-945d-30e337f17089', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', 'bcdd9834-33e1-4fb5-ba83-9259e37b51c3', 'abae95f4-6a73-4075-945d-30e337f17089', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
