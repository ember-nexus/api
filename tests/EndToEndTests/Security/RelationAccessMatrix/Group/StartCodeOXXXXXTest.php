<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '02IiNr0GOoKiPMLGGvEL6dNHtOFlikIuGt2LD', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMfgOOGiRJ54sj5gYyxKYXz2', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '02IiNr0GOoKiPMLBrMBYrQYyxYtOpM2Dsi4Lf', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '02IiNr0GOoKiPMLYariUG3fQq99aqsS6IqDP2', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '02IiNr0GOoKiPMLZPrZCLSnYLBodIKy6ZrzEV', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '02IiNr0GOoKiPMLyrNeok4oKyyMZ1FIBKqA0G', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '02IiNr0GOoKiPMLtGBSOc3YgNGGoX86Hc3Wt2', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
