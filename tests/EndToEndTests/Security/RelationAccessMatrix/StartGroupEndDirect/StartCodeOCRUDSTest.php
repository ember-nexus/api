<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '02IiNr0GOoKiPMMTim0JYisqy66yhGLe34FJM', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '02IiNr0GOoKiPMLLd1zOBIEifCENg5VHCEpYE', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMpmm7LubUqJT46c8Lr0cXxd', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '02IiNr0GOoKiPMLtiRKPGmYkXFhBMWPAw4xWA', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '02IiNr0GOoKiPMLPht6byGYnOAfHmzcDHzXHo', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '02IiNr0GOoKiPMM4HEnoPGrL7gB1Cq5HqU9wr', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '02IiNr0GOoKiPMLa5MbXMM0VEyipeHdgvCK5w', '02IiNr0GOoKiPMM8CgB5kneZTpxkvBqukSFqu', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
