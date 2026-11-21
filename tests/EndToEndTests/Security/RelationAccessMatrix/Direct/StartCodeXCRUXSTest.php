<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMgI8XfAjD3U4o2d0IoiCg4H', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMOKnQnLxxYSYORFB7caAUni', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '02IiNr0GOoKiPMMGB7aUFexePrwU5y43bcfvr', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '02IiNr0GOoKiPMMxjKTrnnOmRhT20gmEvmIwX', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '02IiNr0GOoKiPMMZEJJYwUCKjWaVMJga2BwnQ', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '02IiNr0GOoKiPMLRCNwu3r6RvVgoFKl0NZlv8', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '02IiNr0GOoKiPMLpIrZlYh75b69TAO48R09M9', '02IiNr0GOoKiPMMsncyRrMC7B0ysRTI1wnUqr', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
