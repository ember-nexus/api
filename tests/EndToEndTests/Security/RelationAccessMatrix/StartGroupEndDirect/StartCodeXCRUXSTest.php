<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '02IiNr0GOoKiPMM1GSydWm6v6vDNPUnBgAiue', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '02IiNr0GOoKiPMLAlOgY6wWNRXssIGgjDQz12', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '02IiNr0GOoKiPMLZXMbA1nLcs1IOq74w38c7L', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '02IiNr0GOoKiPMLaErhvweSMr8xNBAFJfEp3c', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '02IiNr0GOoKiPMLEVVKnAWpvS2SAZQ19P1YUJ', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '02IiNr0GOoKiPMMBpGdT9vHNFIsGi4NRE7huJ', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '02IiNr0GOoKiPMMVwTxjgRBmtAuQXSbzFEZe4', '02IiNr0GOoKiPMMOY17mHi6kF0XYIWS0jHu1t', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
