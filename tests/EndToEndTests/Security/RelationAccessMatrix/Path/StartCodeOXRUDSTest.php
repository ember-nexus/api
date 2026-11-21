<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '02IiNr0GOoKiPMMHZsLWzcsw90hNhD37vpKMS', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '02IiNr0GOoKiPMLFTub5AzHTqrtFJYix9arwZ', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '02IiNr0GOoKiPMMCBtNC17XEjTqVoKzazH6tG', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '02IiNr0GOoKiPMLo1bnAZUr1vq6D2oTTSIdVn', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMuPfgukK97k1J8piPpScAe9', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLnKA3OnCmyljn3EqhNXeVbu', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '02IiNr0GOoKiPMMslh0NZEza7evo78LzCOyDr', '02IiNr0GOoKiPMMpIdZlvAGiRDNtMHcaZ3V58', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
