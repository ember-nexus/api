<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '0cbce0d1-987b-43d5-893d-6359678534e8', '2bf481aa-83bd-4909-aede-c91d9a5a698b', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', 'da1e01a8-f47e-4d9f-b88c-5be5181054af', '2bf481aa-83bd-4909-aede-c91d9a5a698b', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', 'ae0b74fc-3a91-41b7-b756-5f501d472929', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '6e4b40a7-c700-4c2e-b771-023fd24c3cd2', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '3c4cdc78-89dd-4b8b-b385-6245f463bc7a', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '78c5fca6-3e02-4081-9e7e-d9a3bd004223', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', 'f3fb3ee3-e65e-4872-a7b8-980c08d266c1', '2bf481aa-83bd-4909-aede-c91d9a5a698b', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
