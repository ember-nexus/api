<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXDXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '02IiNr0GOoKiPMMQHb7nMmf0cXdvRRelJXyz6', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', '02IiNr0GOoKiPMMv5BmoGRRFX15vTXb14lkuH', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '02IiNr0GOoKiPMLbQSP9vdA7c7ElnYTAAc58x', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '02IiNr0GOoKiPMMdBebhDiLRLRZgHqnsQMA8D', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '02IiNr0GOoKiPMLS58jKUVn5ldzLRirIoziiF', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '02IiNr0GOoKiPMM8u5LYPyQZhY0w0tXqWuxkN', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '02IiNr0GOoKiPMMqMj11GN2OeCebGjW0tvCI6', '02IiNr0GOoKiPMMyuvDo6gytdTBGrTvj82V3N', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
