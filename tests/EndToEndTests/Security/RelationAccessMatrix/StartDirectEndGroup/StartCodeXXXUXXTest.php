<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '02IiNr0GOoKiPMMBdvbbxkYkaOHSKZAvoHlIj', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '02IiNr0GOoKiPMLjUPdMai3nPl7gPaBAHmfDR', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '02IiNr0GOoKiPMLKtjEcBUeiCMgaFiaC25fXb', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '02IiNr0GOoKiPMMnLrQeYm9WjP6k9HVRWWn1p', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '02IiNr0GOoKiPMMWwmnmGoLvDPzbgU1gpWR4R', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '02IiNr0GOoKiPMLd4Fp5dLi0b1jLk1q0WoXVQ', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '02IiNr0GOoKiPMMlLLx29Uht6EfkAxrGumaeX', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
