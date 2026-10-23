<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', 'bbe7de16-5a09-4cf2-bfc4-0c69f5f2f14a', 'a37c6f44-885a-48f1-b39a-7731742ac5be', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', 'bdc1bf78-8032-41f9-b4e7-d60c87194141', 'a37c6f44-885a-48f1-b39a-7731742ac5be', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', 'ec7ccbb3-61e7-4ba0-91c8-87a498f3494b', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', 'fc4f0550-34c4-4ee8-8b41-dfb87bc20a7d', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '6241f536-d639-4c1e-904a-0efbc0686675', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '3c3b4c7f-aa17-4800-ac4c-7681b6b8a2b4', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', 'f6d353c1-bd2f-4d60-a072-f61f702b6a78', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
