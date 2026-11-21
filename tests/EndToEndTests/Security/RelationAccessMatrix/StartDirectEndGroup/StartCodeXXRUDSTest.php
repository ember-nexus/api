<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLaEsOTViDvH1Apa5AHzL4Qc', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '02IiNr0GOoKiPMMF6zqzcycMuO1ebh6ZBtmZH', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '02IiNr0GOoKiPMLOYFtu4NIF3MMh8MnAj3SFM', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '02IiNr0GOoKiPMMiyALb4izN1LRoB1YWI9yzS', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '02IiNr0GOoKiPMN3IfG4xTkzMxGXNIHGN8p02', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '02IiNr0GOoKiPMMUOJu1Mil0gWGuJW8aE9yVR', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '02IiNr0GOoKiPMMbXjMUVIPD7XAQJb3ZkZaOn', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
