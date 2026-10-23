<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '25116d4d-0c7a-4bc1-8ecd-be5f82c80cb2', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '7a9f5b76-c0ec-41a7-b0ec-2db596c600ee', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '5dda413b-30b2-4c8c-bf48-ab25ba5b0a89', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '239d5634-b3e5-4855-93e2-626e80f35636', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '88daab4f-0af3-48bb-9ee9-6bbb54761372', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '25678b64-b73e-4c9d-a4e9-73c40af6eea4', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '16feb737-802f-4dab-b88d-803538f18103'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '322aa017-f58a-4237-8aa5-4eb99b151662', '40e48f8e-9c2b-45b8-b75e-7a5cb2f4ce2a', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
