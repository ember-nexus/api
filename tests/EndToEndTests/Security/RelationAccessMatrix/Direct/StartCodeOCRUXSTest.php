<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMWr87arLnOatfNeMWcdhJsu', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMH3oJFsR67C1n92Y1RRfCkw', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '02IiNr0GOoKiPMLOBafBytGegxYRaJBUGc5uz', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '02IiNr0GOoKiPMN0WvuiVxqUY9lCGxpYjnPnc', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '02IiNr0GOoKiPMMmADz3LMdcgISL4bQ1wJusT', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '02IiNr0GOoKiPMMc1sCURIgiN1RUVHg8H3SWl', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '02IiNr0GOoKiPMMz6GB6N1sg50WSBQQ6eYEV9', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
