<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLfmGWHZVsieN62isP6QZtMF', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '02IiNr0GOoKiPMM3Xw3AordwUvP7l5dmcgvyK', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '02IiNr0GOoKiPMLvj3nm8ddGwaBh5XQpeyFaq', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '02IiNr0GOoKiPMM2IellJDuexPQxFJIsl8ob6', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '02IiNr0GOoKiPMLx7rjm7HSUdViB0iNPB20C1', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLi5fdhbAAGQ7bx2ERHzEzv7', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '02IiNr0GOoKiPMLpUExYQ443cBnSY9TQ31aud', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
