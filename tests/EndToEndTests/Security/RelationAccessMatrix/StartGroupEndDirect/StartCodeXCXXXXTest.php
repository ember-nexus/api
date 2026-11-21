<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '02IiNr0GOoKiPMMAH1ciOFxWXyO6QBa2kaexL', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '02IiNr0GOoKiPMLmrxDPo08UJlOnVHAl4HJ8H', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '02IiNr0GOoKiPMLdP1qXMBciVQqnGqG4bCXpY', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '02IiNr0GOoKiPMMIYIsUAaiKE2HRXdad8flTD', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '02IiNr0GOoKiPMLH3z1sYyo53XNfLvU4tyBCT', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '02IiNr0GOoKiPMM2qWy6F30UnJZsfQZHr2q9z', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '02IiNr0GOoKiPMMau8XUueMHmvj80GtgRCFv7', '02IiNr0GOoKiPMMSXE6PKlHG3N92rm03OfR4q', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
