<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '02IiNr0GOoKiPMM21bK0JCvQJ9bcsTSrkn9GQ', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', '02IiNr0GOoKiPMN3wF0LIXfwhEcMv29I1dMyj', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '02IiNr0GOoKiPMMnI55CoD9vDLPQqkltQTYVp', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '02IiNr0GOoKiPMM2S5iJg4PIH8RVGF6Va5fPS', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '02IiNr0GOoKiPMMvPvEmQII4I3AuoZdzrEjcy', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '02IiNr0GOoKiPMMAhOZcCEdAItWpZ7Tmgi7kD', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '02IiNr0GOoKiPMMuK1FmPIJ2l4GTrO8mna10F', '02IiNr0GOoKiPMLzvRa26O9ov3mCcapezWF3B', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
