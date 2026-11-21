<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '02IiNr0GOoKiPMMJ6D2d2uM5WtCYvW0UxEfZL', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLSmT01YNHyUNBWReTwTzxup', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '02IiNr0GOoKiPMLxQhHyjkBCEP0PPw9qIU3pd', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '02IiNr0GOoKiPMLAlOTTyHubpIBh9IG6mKue3', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLMQ6bZw3AuSuloTDwVLf89b', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLHBWxmGaLLNBbQzfDsHbHPa', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '02IiNr0GOoKiPMLlJjQbeZ1pjVc9iep3xqnl6', '02IiNr0GOoKiPMMyOpBvSbYNHRl21ec0dXrO1', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
