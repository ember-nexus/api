<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '02IiNr0GOoKiPMLXC54aG8dTrEKQpRn2K19f3', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '02IiNr0GOoKiPMMwyBDZBOKyiZ9WvKDvL35GF', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMp7JUKqXKQmvd78Sy1AnFo2', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '02IiNr0GOoKiPMLBHXzq4BDjlfQu0kolts0YO', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '02IiNr0GOoKiPMLTCtNKa7k9APKevmKckXqIK', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLUqltyQIJ6x2NpZSDUE9dD8', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '02IiNr0GOoKiPMM9MNiWXSu3JBA0tHMmtMuXj', '02IiNr0GOoKiPMN6eEaqMcKic6dcIpuWqWYxp', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
