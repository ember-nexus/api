<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '02IiNr0GOoKiPMMxW796eCMG4vhCYrsFdn5Gp', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '02IiNr0GOoKiPMLXWtGcvkhpHfVjhysbVDujs', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '02IiNr0GOoKiPMM1TguG4RY11fhVd666gQ2a5', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '02IiNr0GOoKiPMLKAYChkdUTXRQCoIUZbLF3x', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '02IiNr0GOoKiPMLmvgHcnpUfjp0WJPa3MyXmz', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLuKHBYEIInAuiuuHrigalOX', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '02IiNr0GOoKiPMMI9jieAKUePVWFLDdmY8iq7', '02IiNr0GOoKiPMLJ4caludJLhvPnDaSjtSABE', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
