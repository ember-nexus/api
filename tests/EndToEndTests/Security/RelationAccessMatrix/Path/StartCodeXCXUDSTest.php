<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '02IiNr0GOoKiPMM5yw7rGzCwbJeUQnUHZ6gEw', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '02IiNr0GOoKiPMLJJebI0ZYwa5vF0tooMYzrb', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '02IiNr0GOoKiPMMtahgoVd5woiO9cax1PNREn', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '02IiNr0GOoKiPMLsz8RGg5MpF4xJFcgKjdYfN', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLXxIjQn5nbJVnYcBA1SSTKU', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLT1bqaXKZvErhxnZfG0nDgs', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '02IiNr0GOoKiPMMNsVr9e2dRRi47ZP7M23YGY', '02IiNr0GOoKiPMLKpzThPne7hFHOvsMFRe7sr', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
