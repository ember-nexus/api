<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '02IiNr0GOoKiPML7KF285OalE9eaL8gW04nQN', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '02IiNr0GOoKiPMLZKDAgwLTpU1Nimb4Ea02cM', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '02IiNr0GOoKiPMLO40R6QN9QMINrSj7sdpgOr', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '02IiNr0GOoKiPMM2QBn7qs5TMlKfgE97JTZiP', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '02IiNr0GOoKiPMM4QkTkNwhSh6Dveivp7tqWA', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLf6lfzx6UO8gKuRGOaVXBc8', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '02IiNr0GOoKiPMMc9TqzGIOcKU2VX0hXIBENX', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
