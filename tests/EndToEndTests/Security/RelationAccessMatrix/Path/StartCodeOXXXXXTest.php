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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', 'b9ae3b95-856f-4032-a2be-c164a0f9adf1', 'c648b663-7eea-4941-8565-62a81634a121', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '7c418d5a-378d-4371-a1fd-24d8c625d9ee', 'c648b663-7eea-4941-8565-62a81634a121', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', 'ef2c4234-029a-438e-8880-90df7ce6a483', 'c648b663-7eea-4941-8565-62a81634a121', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '94414c3c-0161-4e68-a647-66858a071505', 'c648b663-7eea-4941-8565-62a81634a121', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '4067fbbc-c31b-47a7-b963-fb72179943cc', 'c648b663-7eea-4941-8565-62a81634a121', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '7e479f46-b36b-47d1-8abf-0e056a0cc158', 'c648b663-7eea-4941-8565-62a81634a121', '16feb737-802f-4dab-b88d-803538f18103'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '68edc829-0e80-4d79-88aa-9eee6fd9446e', 'c648b663-7eea-4941-8565-62a81634a121', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
