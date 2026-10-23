<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '1f6aa9bc-a081-4f4c-9dca-0c1848b736a6', '2a46c844-be29-4535-8373-6b299cb47fdf', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', 'dabbc4fd-39f8-489a-ade0-f22e59e3d1bb', '2a46c844-be29-4535-8373-6b299cb47fdf', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '1877ca95-21ba-4390-8601-f5bdaccc1f72', '2a46c844-be29-4535-8373-6b299cb47fdf', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '7df7e824-8cdc-4665-a348-1fbea46093e4', '2a46c844-be29-4535-8373-6b299cb47fdf', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '01f7e5bb-b161-4e1e-93b9-3aced9dacd44', '2a46c844-be29-4535-8373-6b299cb47fdf', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', 'b1f49c2c-4621-4aab-9072-04db62aa30a7', '2a46c844-be29-4535-8373-6b299cb47fdf', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '891fef2d-d460-4d37-a73a-86ec00f5f8d7', '2a46c844-be29-4535-8373-6b299cb47fdf', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
