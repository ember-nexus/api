<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '02IiNr0GOoKiPMMeJP7ZUHHMItPyuEmE3Feuz', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMs6GrNIUzFOZX8pDBtuPvYz', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMTEbhLK04meIWYzi8Zx7DZd', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '02IiNr0GOoKiPMLCahHZ1AnTMSRsxDPosjwOQ', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '02IiNr0GOoKiPMN7HoBfUsI296SWFZyA42MGf', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '02IiNr0GOoKiPMLrFctC2BuTcaUdHoYvr60Ub', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '02IiNr0GOoKiPMMAfUIGKkXKko9T78yl3o6ar', '02IiNr0GOoKiPMMNTxlfOUSwLsMoL4mlJ3uqC', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
