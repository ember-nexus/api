<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '02IiNr0GOoKiPMLfxaV7IZrbTsfy3yWCWmxg1', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '02IiNr0GOoKiPMMhM9BznWV4seU1nCo4Pbgq5', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMIlVqVugE92X1vvmVDrmE2i', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '02IiNr0GOoKiPMLRkLrxquJrmijrdSKlP7NXK', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '02IiNr0GOoKiPMMCvChEqMwJIAxQj2Wc6L56R', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '02IiNr0GOoKiPMMkhkBVeFNypafIxa1Fh7t2j', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '02IiNr0GOoKiPMLhKQdvgsuApbrCrt6SgMwdi', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
