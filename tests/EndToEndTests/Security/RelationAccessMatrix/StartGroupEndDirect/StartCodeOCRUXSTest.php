<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '02IiNr0GOoKiPMLgu5BcqgGhEtI856iVHhazR', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '02IiNr0GOoKiPMLfkMQCnXrY3vqIVyaLOdFiV', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '02IiNr0GOoKiPMLz6UO3Oo2FPkXkvpigPCGi2', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '02IiNr0GOoKiPMMhMByIW9FnjtP6OmpnznQx9', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '02IiNr0GOoKiPMLUF8304QILJXs3nBpTQ8chi', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '02IiNr0GOoKiPMLj42dlpBJOWKEuQHZMqvIm9', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '02IiNr0GOoKiPMLukbMXZGmP8RdH27INCYemJ', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
