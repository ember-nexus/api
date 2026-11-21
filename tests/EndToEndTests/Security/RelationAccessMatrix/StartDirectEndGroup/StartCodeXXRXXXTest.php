<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '02IiNr0GOoKiPMLepjmoVI0hRlEsn38Kj31PH', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '02IiNr0GOoKiPMLdYRisVZ0YQ1CFZfbu25BSF', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '02IiNr0GOoKiPMLGH0L2rMcSE90xlKGgp4q9D', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '02IiNr0GOoKiPMM8xuF7bc0WseH3shVtjwJLD', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '02IiNr0GOoKiPMLk6DWnYk3C1UXViobEziUUH', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '02IiNr0GOoKiPMLSNyrVmynt5udPvf7xiHUli', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '02IiNr0GOoKiPMLyHUFbSTTbyYR7oH1slwDc9', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
