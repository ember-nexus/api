<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '02IiNr0GOoKiPMLqnNLX3KOMlVwjW5bPLnFP2', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', '02IiNr0GOoKiPMMrFSJKwbHpQ4hxwLNCdzTzr', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '02IiNr0GOoKiPMN4MiW88lvcsv1p0iQbz6LQE', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '02IiNr0GOoKiPMMEPdd0KYrLQsrAgWcqkbwjt', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '02IiNr0GOoKiPMLvQCQgPMyC7Tb5pMbGHRjO5', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '02IiNr0GOoKiPMMMCk9m2mTYXwnHAo2UigxyM', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '02IiNr0GOoKiPMLDLryyOwubBhpFYrDiWvdxa', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
