<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '02IiNr0GOoKiPMLHjU5A2jn6a2SWOMaqIDF2Q', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '02IiNr0GOoKiPMMV7chjwdPCsCuDpgphscXlI', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '02IiNr0GOoKiPMMgmINiq2B0MKO3bhwWxjg6y', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '02IiNr0GOoKiPMN3B3ZFw1P05xZCLXOjR3mYd', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '02IiNr0GOoKiPMMJljCWuASe9rpmbnTs6iTJs', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLuvznGVZAy8uEBpLc8kjJ2b', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '02IiNr0GOoKiPMLvbRmfJPU6W493L5ossDPYl', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
