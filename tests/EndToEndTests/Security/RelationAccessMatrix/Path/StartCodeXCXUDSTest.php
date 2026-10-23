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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '7d58c29a-0ae2-4c89-a871-03cfb3078ef4', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '19670592-37c4-4c5b-820e-c6d6618ed9df', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', 'e338ea44-5882-45ff-bb2e-9e6bb08eb2ad', '1c8567bb-09db-49bf-bc5e-68cd33c69141', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '62a01675-0508-4388-aa4a-d5456c4b6d04', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '3778d20d-de46-4434-b6a1-86f6a28900b0', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '2d588a94-f478-4f55-ab0a-0b9c81405353', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '16feb737-802f-4dab-b88d-803538f18103'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', 'a21daab0-4a0a-4ec8-8105-9c025d3892ec', '1c8567bb-09db-49bf-bc5e-68cd33c69141', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
