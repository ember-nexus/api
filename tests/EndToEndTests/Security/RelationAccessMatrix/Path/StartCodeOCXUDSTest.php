<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '02IiNr0GOoKiPMMYJeMnc8swWrgNUHs4O0Wr9', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLV3zil2ghnqIi524vvcvD1h', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '02IiNr0GOoKiPMMZ8aou5ODXxYUYiz5hght54', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '02IiNr0GOoKiPMMgb0LadovHM6MGJ3RX2lDCr', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLzHlYeleYBZnQmana9eymtM', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLWP5YKk1UtVQkROrBkJxvUZ', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '02IiNr0GOoKiPMN5DVo0dW6ZamsM9MveNdw6M', '02IiNr0GOoKiPMN1kPVBJhIRYftTsoRhplYqe', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
