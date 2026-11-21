<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXDXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '02IiNr0GOoKiPMLDPdl1J4kTGGrYP5Zxq6hEo', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', '02IiNr0GOoKiPMMsjpRZxjFxy5GHsMIJf6XGj', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '02IiNr0GOoKiPMLbUC0NZKPlFajpG8gagQxXT', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '02IiNr0GOoKiPMLXzCi2sFGLDnFiVUQCbs3qQ', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '02IiNr0GOoKiPMLfCN6lLmiUIkPzjtaekfByn', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '02IiNr0GOoKiPMLl6PsDBsKlBYYgtVAjuSZtJ', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '02IiNr0GOoKiPMMg3335nXeBqip4I6yjtKIDX', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
