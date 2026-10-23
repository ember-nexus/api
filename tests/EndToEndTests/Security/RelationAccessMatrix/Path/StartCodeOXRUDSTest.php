<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '9529133c-eea6-41ec-ba5d-a97aea0c8d0c', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '11894678-fde0-4be5-a649-0ed6a19a9b14', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '8a1a89d0-4bdf-4d60-9f46-6d69da35018a', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '5876e06c-3da3-4146-90f2-60587919b75c', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', 'e4eb8f66-b966-4af9-96f1-55c0872d983d', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '57007fad-36a4-44a9-9aa8-1e4373e79b25', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '16feb737-802f-4dab-b88d-803538f18103'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', 'e180664b-cdbc-4b2f-aa3e-ed00c1fc5ed3', 'da687cc4-e264-4ba4-a6c1-44bffe738b39', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
