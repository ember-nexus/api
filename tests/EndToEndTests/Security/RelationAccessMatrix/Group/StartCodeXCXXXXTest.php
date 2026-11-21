<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '02IiNr0GOoKiPMMz87m0i8J7n9ij8EmkTap7u', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '02IiNr0GOoKiPMN1elKZlwOJN6dkehPQWbyLw', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '02IiNr0GOoKiPMMSX9Kb0X7aOpF5dAEcNfBAs', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '02IiNr0GOoKiPMLukgAlzOPIOyoEAxcefLWA7', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '02IiNr0GOoKiPMMBYGLV19sk1O3VHkfvvvab6', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '02IiNr0GOoKiPMMPBZNRO7F7lOJN7O73YVA75', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '02IiNr0GOoKiPMLi3oRu70hUhSIYDNgAENEdG', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
