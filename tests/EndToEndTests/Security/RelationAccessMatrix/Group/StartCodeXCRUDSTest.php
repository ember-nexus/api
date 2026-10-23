<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '03db80ab-049d-4f92-883f-ea3bb2824bc7', '00913f2f-e8c6-44f7-b55f-10a594f84127', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', 'b9181a60-5b70-4848-8e70-336ea9581ecd', '00913f2f-e8c6-44f7-b55f-10a594f84127', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '6ad306d1-4c03-4415-9af4-e6c85117be2e', '00913f2f-e8c6-44f7-b55f-10a594f84127', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', 'dcd62de3-9c57-4075-b62b-40242e2174f5', '00913f2f-e8c6-44f7-b55f-10a594f84127', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', 'cc4744bb-e87e-487c-8a82-f828991766b1', '00913f2f-e8c6-44f7-b55f-10a594f84127', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '5283e756-ee89-40ad-8146-1325d4584bbd', '00913f2f-e8c6-44f7-b55f-10a594f84127', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '439c29b8-ef24-4913-bec1-61cd1e892b90', '00913f2f-e8c6-44f7-b55f-10a594f84127', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
