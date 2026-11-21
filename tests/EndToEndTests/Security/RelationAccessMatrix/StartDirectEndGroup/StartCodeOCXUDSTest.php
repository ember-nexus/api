<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '02IiNr0GOoKiPMN02n9J8vnn9Q8cXZ0VtY6Uc', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLoh8ROkkqovno375Ialb7iH', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '02IiNr0GOoKiPMMfriDBOJZ6KaSduFHwfc9s7', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '02IiNr0GOoKiPMMuXC2F5l0B5DCLINLs3exKu', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '02IiNr0GOoKiPMMsaNFLNgzciJT3aNCvWQO3R', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '02IiNr0GOoKiPMMfPKc7GzbOj8GLISkjQ7bt8', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '02IiNr0GOoKiPMMKlxMkIugU6lIbKzoOBrQNm', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
