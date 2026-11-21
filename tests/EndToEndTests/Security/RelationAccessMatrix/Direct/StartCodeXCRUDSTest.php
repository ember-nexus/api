<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '02IiNr0GOoKiPMML4qv1UT3FxCY8qRAzQUSOw', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '02IiNr0GOoKiPMMfkBOnTrdJb2jNwl1YK8V3D', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '02IiNr0GOoKiPMLetTIYwBuy9ANfdWICtF5aT', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '02IiNr0GOoKiPMMcwWbDy7EBtMjCqeEAs5b5w', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '02IiNr0GOoKiPMLJrfFjXWRDiF10HUTObfmd8', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLXgIVFBhviOmb4EP2B6n5TP', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '02IiNr0GOoKiPMN1I3LhDkZArelqhw2eISwk6', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
