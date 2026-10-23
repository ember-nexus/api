<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '6041fa2a-446e-4ad8-b169-49d6695961d5', '67c06201-8fdb-4c03-b672-ded0c7ded693', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', 'a0d8309d-da44-4630-b544-5629cb5b4422', '67c06201-8fdb-4c03-b672-ded0c7ded693', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', 'e5c91f16-39df-41d0-ae0c-3ced8f328d61', '67c06201-8fdb-4c03-b672-ded0c7ded693', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', 'dee9b831-063d-47a7-a94e-b1630ac763f5', '67c06201-8fdb-4c03-b672-ded0c7ded693', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '86605312-3b5b-40cb-a1ca-37ec4e506f94', '67c06201-8fdb-4c03-b672-ded0c7ded693', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '400c7d83-31fb-43ac-8043-80c844dcea4a', '67c06201-8fdb-4c03-b672-ded0c7ded693', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '714452d4-7dde-4245-a5eb-33748a4137a9', '67c06201-8fdb-4c03-b672-ded0c7ded693', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
