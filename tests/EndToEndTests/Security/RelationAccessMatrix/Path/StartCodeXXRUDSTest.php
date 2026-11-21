<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLRtqJME5DwpUVAcvnLW54wL', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '02IiNr0GOoKiPMLEev1XxTBhb0a5qF5CGF33T', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '02IiNr0GOoKiPMLqWLDBsStDHP3lxU7Bw7lKm', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '02IiNr0GOoKiPMLsJe0StadFurbnNzG0qjNpO', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMwMSBn2YuLN9vBpQ95grJAg', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '02IiNr0GOoKiPMMBBemcIWbfaL79OFgbuMd3v', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '02IiNr0GOoKiPMM0vjLsmnTvBQ0gjCDrCZTL2', '02IiNr0GOoKiPMLy2RQL8KL8MATEecLoAD8O9', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
