<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '02IiNr0GOoKiPMMEtnWQQcQznVcsmJrlorQcS', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '02IiNr0GOoKiPMMaxrgY6M5OVNb14Wfr98UNF', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '02IiNr0GOoKiPMMv8wadZKHrIYwbLvvMEnfhN', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '02IiNr0GOoKiPMMKHlJx7zYlaNWwrZunHOyrp', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', '02IiNr0GOoKiPMMobDEZmlKiUvMpE77zB6jio', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '02IiNr0GOoKiPMM25SLENKRrAWpBzdjI9riJK', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '02IiNr0GOoKiPMMFM4MN4kAHecW0SQ9StY1iX', '02IiNr0GOoKiPMLk9zLwoFPelofjlP5EGE6Ni', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
