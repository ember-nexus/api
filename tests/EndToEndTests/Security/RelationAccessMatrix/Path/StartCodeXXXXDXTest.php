<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXDXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXDX -> XXXXXX' => ['XXXXDX', 'XXXXXX', '02IiNr0GOoKiPMMnvfqn4sNMM5y81UVj0Wi7M', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XXXXDX -> OCXUDS' => ['XXXXDX', 'OCXUDS', '02IiNr0GOoKiPMMQ88EmCuOq8n9LmN8gqtFZ3', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XXXXDX -> XCRUDS' => ['XXXXDX', 'XCRUDS', '02IiNr0GOoKiPMMf0rlr4vUL6M92lY30EczBn', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XXXXDX -> XCXUDS' => ['XXXXDX', 'XCXUDS', '02IiNr0GOoKiPMLFfCGNldMn81CVGuyYDLj3T', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XXXXDX -> OCRUDS' => ['XXXXDX', 'OCRUDS', '02IiNr0GOoKiPMMaEWeVCmN1WNL7U2fRCkSVc', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XXXXDX -> OXXXXX' => ['XXXXDX', 'OXXXXX', '02IiNr0GOoKiPMLwF9pRVND2i9K1vXng6zevi', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XXXXDX -> XXRXXX' => ['XXXXDX', 'XXRXXX', '02IiNr0GOoKiPML7XSTPQBKcwsMycywx4dyfq', '02IiNr0GOoKiPMMiQEJxNExqNlkr3zCqGFd2w', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
