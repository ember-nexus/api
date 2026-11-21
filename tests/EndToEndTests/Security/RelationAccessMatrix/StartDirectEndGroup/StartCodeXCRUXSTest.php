<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '02IiNr0GOoKiPMLgqFHynmVZhnFyxQttQneKu', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMaVYFVbmvC5NlVST1mKgztG', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '02IiNr0GOoKiPMLl8PbFikGdmCOwyWVqaFmgB', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '02IiNr0GOoKiPMLEVU9HN2YgQHYI9BCMxX4Xy', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '02IiNr0GOoKiPMMRppS67woFx8zGgKQS1vC7w', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '02IiNr0GOoKiPMLSRlhjhkhvoL9wjElJO59qU', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '02IiNr0GOoKiPMMnq3OohOgdvhT0pEPvSab6F', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
