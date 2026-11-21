<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMRtYMQx3SAIDgi3oWRTsGIV', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMTO4TaIPcWwZ7Mf3qY4KKtP', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '02IiNr0GOoKiPMMjXzn5xmbO7CtaDe4Z7TaLr', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '02IiNr0GOoKiPMM7vhyGWKIXkqlsZKFNTnGmo', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '02IiNr0GOoKiPMLTTvCEdyrtXUpkhYzbnRENe', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '02IiNr0GOoKiPMLp3irjkWhpKRdpb0JI6lwQk', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '02IiNr0GOoKiPMMWc22Kw7BNs31kjHb2Q2HlF', '02IiNr0GOoKiPMLto8aperGxYTMus0tZpWjMq', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
