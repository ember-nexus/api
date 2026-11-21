<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '02IiNr0GOoKiPMMDrceWNNrB2mjE6lx9HBiY3', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '02IiNr0GOoKiPMLkJRTmtyWvOAVwAU83fc7aS', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '02IiNr0GOoKiPMMoZKnAZG80XkGXge6b1GbEj', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '02IiNr0GOoKiPMLwlBi6FGpd9qpPT4ur6Dr45', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMRJh4hRAKtSo74bYbYN4M0Y', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLjBbpxg2m67kx5moifgdT8t', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '02IiNr0GOoKiPMLcnGp1el2n75tqxBGXNrsdu', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
