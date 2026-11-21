<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMaRn2Mc1HPeiKbmpE8Z1VKO', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMbMM0hO2rZ1e6A3z6EYstGF', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '02IiNr0GOoKiPMMy60KMLC9H2FWJzGTYz9BLG', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '02IiNr0GOoKiPMN5nQP2rXJxc6AFfa1qfsgCw', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '02IiNr0GOoKiPMLsnpL6xKMjgtM3cHARdq9ym', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '02IiNr0GOoKiPMLaGjHUpFleKILrz8ePsjeua', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '02IiNr0GOoKiPMN39Aj6fahlTrHzsPn2fj9U9', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
