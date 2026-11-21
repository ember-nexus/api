<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '02IiNr0GOoKiPMLUZoEsq8Jvilp4LiDX5huBF', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '02IiNr0GOoKiPMLuTbqN4RMglHTLbERURmXO0', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '02IiNr0GOoKiPMMKC8yWVcCHshSZNcQqpqPXP', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '02IiNr0GOoKiPMM59z7OKIuBNSRPjlrn0hXam', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', '02IiNr0GOoKiPMN4wZLtaqrjSmSaaqonmJQ3i', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLzLbAhdk21Q3Oc2oDXpFE0C', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '02IiNr0GOoKiPMLFIZmrUDLO8pZTFEcdxvys2', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
