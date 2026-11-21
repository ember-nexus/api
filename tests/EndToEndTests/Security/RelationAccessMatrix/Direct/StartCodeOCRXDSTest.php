<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '02IiNr0GOoKiPMMsx3vBguyiKNIfcrjR7G4Ce', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '02IiNr0GOoKiPMMpIbJXrAvKALNvIcM9BTJfG', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '02IiNr0GOoKiPMMdHEHrtqf2qDEBQspjmxbTL', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '02IiNr0GOoKiPMM5aRu7ED6U28qRVirRyUlcm', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '02IiNr0GOoKiPMLLqKGPNs1uW9g9gsmOGawaq', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLfiR3Swy5uN5JGi7NjpOnR8', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '02IiNr0GOoKiPMMDgI6xA2RbQzF7otuiEf23P', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
