<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '02IiNr0GOoKiPMLegHDVNTfYr9usEGlAkWroe', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '02IiNr0GOoKiPMLJlzwO20LO9wCBbGdHfM8xQ', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMBycIWEfHA8blNfmFg3OFFc', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '02IiNr0GOoKiPMLd6B34BsU2rEwpwXKXw6c8H', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '02IiNr0GOoKiPMME4rrFPxwlOqTBjTLmRo5WO', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '02IiNr0GOoKiPMMnehmmNdhfs7czRPpIaDWd2', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '02IiNr0GOoKiPMN14qqMEhO6WmSdeNNRErBjS', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
