<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '02IiNr0GOoKiPMM2XfkfyukzLTldZgCZ47rpU', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '02IiNr0GOoKiPMLLZLC0ztd6LwpPhdqR1qESP', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '02IiNr0GOoKiPMMz6DE2iyDrwL3esHJvyIgY6', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '02IiNr0GOoKiPMLENxd7H6FacBduPKWcvnTjT', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '02IiNr0GOoKiPMLrhyRkGHcepAXHGt6yLZ4LQ', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '02IiNr0GOoKiPMMnXDBKnJbSFdjDgBs7IS5Ip', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '02IiNr0GOoKiPMMrMuGBMfweNyMY8f9bbanqo', '02IiNr0GOoKiPMLd85bAtWEd8796l2AHjdXpD', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
