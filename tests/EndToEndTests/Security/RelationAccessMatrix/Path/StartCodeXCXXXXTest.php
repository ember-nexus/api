<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '02IiNr0GOoKiPMM4flIGRny0lZIkEir8zFigb', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '02IiNr0GOoKiPMLHa4FLkcu95gXpqTJm5dCeB', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', '02IiNr0GOoKiPMLlWrUFh1ppY6yzzrea5iS1x', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '02IiNr0GOoKiPMMMzlWEsZ7WRwwpCd6HSfRGZ', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '02IiNr0GOoKiPMLP4FjIxvhkjBhJ3PSAsp7Ec', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '02IiNr0GOoKiPMLcYAj4Z8MuMx8eglHV6qi4n', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '02IiNr0GOoKiPMLnPrRP6cThZQ0q4T1YsbgQa', '02IiNr0GOoKiPMMDxJBS7lcjg3kttw0MYBo69', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
