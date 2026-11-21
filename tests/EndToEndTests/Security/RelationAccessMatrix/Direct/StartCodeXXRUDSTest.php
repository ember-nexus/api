<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLrHYgYOYNoVVyCCKIyEF96T', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '02IiNr0GOoKiPMMzpVoBA5rLD60wUnIPOUOrr', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '02IiNr0GOoKiPMLLqEOQnMQMjTxIytweYUSrv', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '02IiNr0GOoKiPMMgPg3FK4usluXQAvuTf8rM3', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMJg1a7aGRS2WjztHqpmb8gI', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLLM3bgFCh3cgp2CJpEHVIN7', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '02IiNr0GOoKiPMMOOUpnEbYYyXxZH5XkIfw1M', '02IiNr0GOoKiPMM1GRi78RCAaY89efnwlXjPk', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
