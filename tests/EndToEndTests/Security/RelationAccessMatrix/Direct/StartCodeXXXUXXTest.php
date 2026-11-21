<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '02IiNr0GOoKiPMLDPZo4nITXs8FZlrTt9alF4', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '02IiNr0GOoKiPMLBLKYEi67F31UXifeIfqJVw', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '02IiNr0GOoKiPMMRFxeNZSIFJ3X8GO2wiqGFb', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '02IiNr0GOoKiPMLfkKErJ8DRHuCOiGktJEt13', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '02IiNr0GOoKiPMLU1sipT6N6Ao3mwB0H9ykFw', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '02IiNr0GOoKiPMMCaSGf6bbsFSUoljEdlqrri', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '02IiNr0GOoKiPMMc0480crK2bEuwzAzPtMyV0', '02IiNr0GOoKiPMLmo5Qy5geYKeQ7xoUZKUBwW', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
