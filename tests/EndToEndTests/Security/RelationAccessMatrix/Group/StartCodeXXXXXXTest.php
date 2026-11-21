<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '02IiNr0GOoKiPMLx7mPgO2xhpt4cqtY9H3m4Y', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMXJQbNPi94aBrxxZHZPpFDT', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMejpHBAkNM8H1A6dWHcyJxl', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '02IiNr0GOoKiPMM8qL6Ih8gLsInM5CSfI7F5O', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '02IiNr0GOoKiPMLpwVxDmj0qdps6Kd3gUueMy', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '02IiNr0GOoKiPMM0asqBIYs0SwgMq9fZVLYbF', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '02IiNr0GOoKiPMM3yPckAYQ8jMXkLOIn6oeG8', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
