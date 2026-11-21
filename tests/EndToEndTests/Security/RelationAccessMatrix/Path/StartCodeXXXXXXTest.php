<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '02IiNr0GOoKiPMMzx7mLqQWFVRIMhEFDXI4QJ', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', '02IiNr0GOoKiPMMHW36yk39ONFCxPMuvCyDut', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMC2S8Semwuf0pixeaW8A79v', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '02IiNr0GOoKiPMMqlEJ96E2rLW1dOvwDUfm1z', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '02IiNr0GOoKiPMMbKSQUR9SgZJa6oRuPQDH1Y', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '02IiNr0GOoKiPMLLd83ojUqMPFykpTsCGNY14', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '02IiNr0GOoKiPMLoVlV08UjWCyN7Y6gBYt62X', '02IiNr0GOoKiPMMWDS2qwi3dW5krP07DT98fj', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
