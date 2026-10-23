<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', 'f07fab1b-cb52-4128-9463-b882f5ae0d36', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '59d8aa73-d7d2-4995-8f05-5717c6770bec', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', 'c70d6d18-190b-4e4c-a8c7-680af9bcca5c', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', 'e52800f2-c0eb-4253-8a32-c88c78095413', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', 'e120d3b6-cb88-4ea2-b70b-78183adec09b', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', 'c6131d07-9a3f-4ebe-b444-ac2507de0ecc', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '9bb220d4-f471-4501-adae-ce63f5b9ee1c', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
