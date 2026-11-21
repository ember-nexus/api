<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '02IiNr0GOoKiPMLcDSVuv17t9aMeAy38Xnkeb', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '02IiNr0GOoKiPMLjuqotgYk4xCOaJh7nGyb53', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMIOr9vn5lSzs1R0rD2Z11es', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '02IiNr0GOoKiPMMbTxw9humVpt5vDJkautvNt', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '02IiNr0GOoKiPML78sJwwvne6g44PY7bfde9Z', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '02IiNr0GOoKiPMMc5iDUxMtWWx84oIyXEgtGQ', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '02IiNr0GOoKiPMMwyBHbNVl4mrtveq0wRto9e', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
