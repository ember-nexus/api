<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '02IiNr0GOoKiPMLMEpWn9TmNUf4n4uMuZdihY', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '02IiNr0GOoKiPMMpS62OAc8wtYwUItx3Dmt3e', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '02IiNr0GOoKiPMLIrM4EMFBekYv1PVBvYQ3LV', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '02IiNr0GOoKiPMM6HnevDpb8GpdFRAPEkU0JZ', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '02IiNr0GOoKiPML7u2sUtOfVEqns2RRIWxJdS', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '02IiNr0GOoKiPMMVbjlhsE8u4R5diMqRLHO4s', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '02IiNr0GOoKiPMMBhjxh5W1zmqGtDXJrPewvq', '02IiNr0GOoKiPMLRX8Q3flePXzhtExusirhpL', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
