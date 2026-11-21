<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '02IiNr0GOoKiPMMrOsmYPIB4YfxnvSK8I1cvu', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '02IiNr0GOoKiPMLadJ9brb4Rz3Iex7dWWEAXi', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '02IiNr0GOoKiPMMS2xocvuqlM06ECMMmUd7Xl', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '02IiNr0GOoKiPMMjOYTmmmm1UYp1C0X2D2UL9', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '02IiNr0GOoKiPMLzT7tyAMHfJxSvJ33bAfOCB', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '02IiNr0GOoKiPMMAMc796sF4kdmj7oFjPJUTK', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '02IiNr0GOoKiPMLIP4eCx50kkEzVqQzSgdVFb', '02IiNr0GOoKiPMLSvyA81iWnUG1tUJFZuJBBk', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
