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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '2b0f7fff-dbf8-407f-bffd-46e2e84a6268', '6d096818-9a14-4962-a351-82c556bd8924', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '0fd33101-8a72-4f85-8071-5dc6b4a8efb6', '6d096818-9a14-4962-a351-82c556bd8924', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '5d96fbce-1664-4a84-b1e8-86affd1ff476', '6d096818-9a14-4962-a351-82c556bd8924', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '61432613-c48c-4f14-add8-319f9eae7d14', '6d096818-9a14-4962-a351-82c556bd8924', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', 'e8edb973-788c-4794-b336-1cd8c04d8f85', '6d096818-9a14-4962-a351-82c556bd8924', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '880f4206-8faa-4b03-8bf2-6e29427434ee', '6d096818-9a14-4962-a351-82c556bd8924', '16feb737-802f-4dab-b88d-803538f18103'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '72fd0854-4565-4c67-9ee9-628d95dfb993', '6d096818-9a14-4962-a351-82c556bd8924', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
