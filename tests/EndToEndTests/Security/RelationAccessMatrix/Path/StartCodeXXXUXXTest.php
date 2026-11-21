<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '02IiNr0GOoKiPMLP0TbnxrKYqnmH0bx75hoYo', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '02IiNr0GOoKiPMM4dxTiIWOJUH7us5FRClvEM', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '02IiNr0GOoKiPMLqdufyRvnYY8F6j69naSoGt', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '02IiNr0GOoKiPMLOHHEfv17ClLFgzkTPNktF0', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '02IiNr0GOoKiPMMBa9aPlcanb6ozYSamNis2T', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '02IiNr0GOoKiPMLP9xMlGXgM9YpG0RwQZOz8c', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '02IiNr0GOoKiPMLVMvD0SYG8KyGCG89NGPp0s', '02IiNr0GOoKiPMLcYCcts3ZzoSUF4wrNPbIu1', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
