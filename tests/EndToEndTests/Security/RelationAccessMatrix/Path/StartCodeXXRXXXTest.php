<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '02IiNr0GOoKiPMLjJA6uuFiQbETzrvQCN8Kuo', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '02IiNr0GOoKiPMMEELPuibMqYvC5ojBKqUWfl', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '02IiNr0GOoKiPMLoQ6Cu4gcOImcyy64WzZo1n', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '02IiNr0GOoKiPMM0X81a03GRnpEDZp4xADG7v', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '02IiNr0GOoKiPMMEI150ozLOWc7wcPzeQ4cWp', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '02IiNr0GOoKiPMMiOEo2HgUKOnqdEcxRPZDOw', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '02IiNr0GOoKiPMMvexjvPEiI0WWGrDdKVZ04c', '02IiNr0GOoKiPMLy0YPTEDzDrPC1dYOxCjzWo', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
