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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '55c2b412-2bda-41f4-9987-987225ce5214', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', 'dd2d1cb7-2e1f-43f4-86d5-603c6d05e2a2', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', 'accf3354-059d-412f-871d-445289986d73', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', 'b97d96b8-d675-41f9-b4d6-1f299734c9dd', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '39698577-40ea-427f-b760-8af22e6ec881', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', 'd90e81f3-5b1b-4e1f-bb56-187eecad1c08', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '16feb737-802f-4dab-b88d-803538f18103'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', '291f630b-46c0-4fd7-9bf7-3a8222d2702f', '4c11d835-7bfb-49c0-8834-2a9b0129ac24', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
