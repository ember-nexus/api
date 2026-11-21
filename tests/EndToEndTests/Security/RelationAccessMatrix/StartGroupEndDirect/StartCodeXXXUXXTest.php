<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '02IiNr0GOoKiPMMfPJKlWd9sBbImvS9kuaSEU', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '02IiNr0GOoKiPMLEXLgVY2hDSzAO1nzLNozkv', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '02IiNr0GOoKiPMMVQO5CgMkJSyumO2gHgPccO', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '02IiNr0GOoKiPMMOZuoDWpqDDBXEYy7upLBDt', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '02IiNr0GOoKiPMMgI90HglgrvdzqFwHVyLyra', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '02IiNr0GOoKiPMMpOIFr8ei6CENEf7LuhFJeb', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '02IiNr0GOoKiPMN4BK67vMk1ZZnz6mFQhztV4', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
