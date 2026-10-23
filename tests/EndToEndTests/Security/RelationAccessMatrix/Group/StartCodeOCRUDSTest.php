<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '47ea8afb-1a41-4017-9667-0577054ffa60', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', 'ca19d4a2-a5c8-48e8-8ab2-6d537544511e', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '979f3325-1919-45fe-8643-27a5e70ef387', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '2ab7e27d-4cfc-44a9-bda5-9e43d21b2877', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '8b9dd641-e6e2-4165-991f-88c93671e2aa', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', 'd0fdd938-22a1-453c-9d0e-2b74ed3e3235', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '4ab0ab28-3d0a-4549-9208-fb67205dfb7f', '81e7e969-e2e8-4c6b-98d5-68abba1b2996', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
