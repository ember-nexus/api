<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '02IiNr0GOoKiPMMZLuDqIlOcmuQRwgSzUW18i', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '02IiNr0GOoKiPMM5SnxiIFWOqtpKw6IArxrTQ', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMzP8kgWUGtHUKg3iBfzVklw', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '02IiNr0GOoKiPMMH9OtpL4PiilpGjwPBtbywT', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '02IiNr0GOoKiPMLcJ8FgJvu4ZPEYtL8GLKesY', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '02IiNr0GOoKiPMM6REUTiyxjGi20QdAZT2kjZ', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '02IiNr0GOoKiPMLw1vvhrqiigLOySq3WXmYNY', '02IiNr0GOoKiPMMfV29Kj54gsOpzGlUzWcGSh', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
