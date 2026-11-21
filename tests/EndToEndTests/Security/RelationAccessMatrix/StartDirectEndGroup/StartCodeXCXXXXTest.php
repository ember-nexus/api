<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '02IiNr0GOoKiPMLRq4LJgn6nWfpP8cxFWSRoX', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '02IiNr0GOoKiPMM1CfXKq7lXCpg848XGfgcvD', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '02IiNr0GOoKiPMMau7vhhH6RptmD0uVS8NBJ5', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '02IiNr0GOoKiPMLOr86NzyW2lguxeP8DxtqSC', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '02IiNr0GOoKiPMMGh8HFNmwyJ8mJmV6SYV5x3', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '02IiNr0GOoKiPMLeYjnu5agV2SB9xsEdcT8ne', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '02IiNr0GOoKiPMMh3EJpfdawRzZmYbVB2fS0e', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
