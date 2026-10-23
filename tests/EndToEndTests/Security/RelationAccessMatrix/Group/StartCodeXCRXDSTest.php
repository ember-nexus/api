<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '7f3340dd-0ff3-4b99-ac5c-a200a88bdbd2', '446f11b9-c9f9-438c-a822-c84dfce5ee98', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '5a04fd29-1cd8-4c34-8a37-ca4314fb39e6', '446f11b9-c9f9-438c-a822-c84dfce5ee98', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '718a703c-0ea0-4f1d-8fbb-5dfee80e11f6', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', 'e9b22724-2786-4739-a48d-9cad3fb053b0', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', 'de46e5f5-1b23-4ad7-862e-ec69f3be23be', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '0bc8da65-9920-4e39-be89-bbb25e5a18d7', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '1132f44c-3da2-4819-94da-10aa259d4527', '446f11b9-c9f9-438c-a822-c84dfce5ee98', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
