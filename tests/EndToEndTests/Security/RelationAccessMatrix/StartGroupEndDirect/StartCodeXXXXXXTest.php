<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '02IiNr0GOoKiPMMTsEcNiLW00VuMtw5FPUaTm', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLpF4UMceTMZaoewlcvtmWye'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMjfZn0x5IjHrZXJwl8RZLJ4', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMI41v7tAQtcW1kvO7KGEgeO'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '02IiNr0GOoKiPMN1LqSpnURfnj0esQEXaGS73', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMLBtCGIUqgZj0AV9stmnFIbw'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '02IiNr0GOoKiPMMGwFjO2tQChnkDTbfDyt3uN', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMOOY9KdXJ4UDJnVUP8Ub8Js'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '02IiNr0GOoKiPMLPP5l892G8sJl3UXQoIdPGD', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMXA0f4Cx1oiCGuLLOE5dLKW'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '02IiNr0GOoKiPMLRVIA43halIlKqqPXeKEJGt', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMJ2QBOGmGHFnoAkeH4G0Ryw'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '02IiNr0GOoKiPMMyDZe4gfe8urX0EcDcx4pcL', '02IiNr0GOoKiPMLqnJa8mGzRMc6fbxwl9fKOx', '02IiNr0GOoKiPMMMVa83QgS4tnHXdbaYBf2dp'],
        ];
    }
}
