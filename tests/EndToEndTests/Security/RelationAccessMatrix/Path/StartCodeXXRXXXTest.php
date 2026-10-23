<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '4ecb8923-3eb1-42e7-b135-6a25d825924b', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '8e4df28d-555b-4626-959a-e67defd64bc2', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '59417049-5f37-4412-a441-6fcacc7ecaa3', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '72235851-d1b5-48c8-861c-8ef1c8e6183a', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '8e601d42-c243-4e95-a83f-fa5839e1e1ef', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', 'cc30cd9a-1082-4478-9ce1-cd0a68c212dc', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '16feb737-802f-4dab-b88d-803538f18103'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', 'e77169f6-4d42-4315-8d8e-53618e856c62', '6cf95bf1-47c8-4dd9-98ea-dc49b9bed423', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
