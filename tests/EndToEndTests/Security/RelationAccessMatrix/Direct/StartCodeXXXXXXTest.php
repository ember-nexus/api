<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '02IiNr0GOoKiPMLubC3qj3woJbwWo31zeuYUq', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMECMlfGPjSQ69tst3KveW2w', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMoqHfZ3Ii3qC95TdQE8g8Ua', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '02IiNr0GOoKiPMLRtnO8QLt7RDYtWksjvqZs9', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '02IiNr0GOoKiPMMfvQ0gKkrDaTp3OpVoz0kgv', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '02IiNr0GOoKiPMMpsROI02MVzwvtdldyvfxUD', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '02IiNr0GOoKiPMN0YpB1f8D3IhUBwh2SEHP3A', '02IiNr0GOoKiPMLVHDwuvx4Cs4CASsMo12VX8', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
