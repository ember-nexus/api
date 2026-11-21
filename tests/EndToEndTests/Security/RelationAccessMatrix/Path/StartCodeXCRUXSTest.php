<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '02IiNr0GOoKiPMLmiRDNes9y7WdHMGSQ9NnJj', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMqdhLsZHeCLMI8hFIlHV1xi', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '02IiNr0GOoKiPMMT5CFGLQPyhJDksk5Lp20fW', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '02IiNr0GOoKiPMMZGEx1TdzEJy5yffi7KXlmx', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '02IiNr0GOoKiPMLYtnoXayPnh0kNpS6YtYSU0', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '02IiNr0GOoKiPMModAUoe8S79EiYgcBuUaEIN', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '02IiNr0GOoKiPMLQxLUtsPEKLO50xhYUomXIG', '02IiNr0GOoKiPMLhzx0mfG4hyT4DjN8Yz8dRv', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
