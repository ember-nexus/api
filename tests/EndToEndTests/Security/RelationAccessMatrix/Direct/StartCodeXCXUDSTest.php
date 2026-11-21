<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '02IiNr0GOoKiPMLwblNnChDbP5JnRB8nqabDK', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLK4mhevQgidjLX3ymVMeIRL', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '02IiNr0GOoKiPMLaZWnbAZM5Ql8BbqYOkW55Z', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '02IiNr0GOoKiPMMBwnt6KdvhXY1JQ7i4tgHX4', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLmNfnMe9LinInbse37ddIZs', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '02IiNr0GOoKiPMMcqv8hoXmnj8F0cAHhfcJYR', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '02IiNr0GOoKiPMM3ZsVI7e2Fdux9L6BTTMMP5', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
