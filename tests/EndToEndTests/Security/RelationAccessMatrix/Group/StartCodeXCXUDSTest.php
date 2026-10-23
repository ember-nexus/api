<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '71a606f4-a33b-475e-97e8-5b5db8eee35c', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', 'e6241ca5-8677-4a46-a3dd-e525eed26c15', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', 'f2cba27f-cbc5-45b7-a084-93bbe5b6085e', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', 'd66b0c8a-1a4e-40c1-aecc-789703d30f91', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '6c230680-405b-4e8d-b368-dda2bdddbe82', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', 'b138089e-6070-42dc-89d5-e21fd88c42fa', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '50d4ff3e-3d15-497a-992e-4f7fbb615d43', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
