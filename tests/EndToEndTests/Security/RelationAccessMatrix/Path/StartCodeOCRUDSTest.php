<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '02IiNr0GOoKiPMN3ZdmCxOpFCadGRXG2Zd5t3', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '02IiNr0GOoKiPMLL3CuAbkiIvszeU6euOueoS', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMXJRc21jN89PqqsXKVM1LDa', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '02IiNr0GOoKiPMN3qcZqbKsnIVexBZJoG9JbC', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '02IiNr0GOoKiPMMK8OvXn5VSbf5QHLG9XtxTI', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '02IiNr0GOoKiPML8fDXkjFGQKyqSYqKPhxLuJ', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '02IiNr0GOoKiPMMSVJk2L7iXlrsKmnRlt3Qw4', '02IiNr0GOoKiPMMpKUDpTvwZoIB547TSiGbrM', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
