<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '8d835215-a4d5-46bd-b6d5-f020e1f2eb7b', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '50dce59e-646c-4b4b-b006-bf01c2341937', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', 'd8e65e7c-5a6f-4f6c-9cb2-188e9528c82e', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '6a695408-4818-4be0-a962-3ee6c7b45190', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', 'a9274a12-d4a2-4e6e-bf9a-0af9711f0ad3', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '4e8adec0-ac33-4f7f-8be5-1ccdf52dc63b', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '4160bf26-acac-41f6-b756-c7da9d461bc2', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
