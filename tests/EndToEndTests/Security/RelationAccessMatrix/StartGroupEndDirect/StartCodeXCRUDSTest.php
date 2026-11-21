<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '02IiNr0GOoKiPMM7VFr2LpYiuJKxuZbl3Ua7Z', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '02IiNr0GOoKiPMMBFKTLvXUK2Qad7KMMDpYW3', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMZCQ2ZpZWv9tN06JFPwho8O', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '02IiNr0GOoKiPMMLrz3hlrs9E7fsXRz0x358Y', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '02IiNr0GOoKiPMLnCbyURRMhPPWh5G8JBmobA', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLVJ5zhkhhvuVZqVmUF6hqm1', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '02IiNr0GOoKiPMLAY9sXN5viDGv6vVuFANFKH', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
