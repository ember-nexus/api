<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '02IiNr0GOoKiPMLEAoUkiZCJdW1IKEYU1reN1', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '02IiNr0GOoKiPMMAlCaFDsavOmMue9pDNgiaf', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '02IiNr0GOoKiPMLs8JUBxsd8U66bcFz3Z0rsy', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '02IiNr0GOoKiPMMzyzO8T7NYdjEjNU5bePOnJ', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '02IiNr0GOoKiPMN35TNmIygBZBQnHk3LaRwOg', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '02IiNr0GOoKiPMN21MKNBuH6SxeHpand4NAEp', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '02IiNr0GOoKiPMMteUSPBfYadmUvFU8W1QkFM', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
