<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '02IiNr0GOoKiPMLEqDnK9wJY5yEmRF464m2xQ', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '02IiNr0GOoKiPMLIUlkCRe5T8769nC0u0qqop', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '02IiNr0GOoKiPMM4yi6aFlNoD1SHaO36xjQh6', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '02IiNr0GOoKiPMMV5czL8jJmwRwaVJUJcocFj', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '02IiNr0GOoKiPMLEmVxcNSGvE6ppq9TvPVFCe', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLUUDxrbE03LqvdpotLdwk9B', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '02IiNr0GOoKiPML8VlmbzUzR8KoPDAB3iGK42', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
