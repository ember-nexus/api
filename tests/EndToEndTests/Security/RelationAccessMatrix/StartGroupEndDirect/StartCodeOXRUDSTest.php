<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLsRGuGJLHI53nf6EYSuJybK', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '02IiNr0GOoKiPMLyNCx9qMcRpNS8Hc68WEgSg', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '02IiNr0GOoKiPMMOyOX56So0dTOfPQap5cxi6', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '02IiNr0GOoKiPMLUQLw56OQcHBwrb4tCoOj9M', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMkMuSGstVkIDFrvUkp8sTb8', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '02IiNr0GOoKiPMMNXjknDo2WIkA54aMhDKKYC', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '02IiNr0GOoKiPMN7aiD2qkn4t7PErkEnzY0LS', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
