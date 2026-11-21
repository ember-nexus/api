<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '02IiNr0GOoKiPMMPhhISOv4ooWwJ1Yr9DMVRP', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLJNOiS7Qhp75VhXZRomGqQZ', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '02IiNr0GOoKiPMLDATT9p02MNXMUPl4gmqWLt', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '02IiNr0GOoKiPMLIg43qzFTnTrv5vlP1i7yVZ', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '02IiNr0GOoKiPMM3qmtCqAzoUHv5z1faaI6DJ', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLyBsacxhI5xk34Xb99dquBK', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '02IiNr0GOoKiPMM6gKQBsiXml0sIZgGx8ayQJ', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
