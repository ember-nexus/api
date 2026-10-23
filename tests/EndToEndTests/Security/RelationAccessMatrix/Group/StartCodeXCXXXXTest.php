<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', 'ee97c726-30c8-413f-bfe3-69bb9a78ba06', 'abae95f4-6a73-4075-945d-30e337f17089', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', 'f3ca6433-df6d-46c8-9d11-1b71c02cefde', 'abae95f4-6a73-4075-945d-30e337f17089', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', 'aba42547-7e0d-4ff0-8f9d-5afa814905f6', 'abae95f4-6a73-4075-945d-30e337f17089', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '664d9bc2-fd28-4760-b0db-7757cd59ec23', 'abae95f4-6a73-4075-945d-30e337f17089', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '88ca203f-f62d-4759-a768-021e2af32ca5', 'abae95f4-6a73-4075-945d-30e337f17089', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', 'a4c2662d-b53e-4d0b-afeb-5c529bc4a071', 'abae95f4-6a73-4075-945d-30e337f17089', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', '4c3dc6b4-635e-4919-84f1-d39a795dfd9e', 'abae95f4-6a73-4075-945d-30e337f17089', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
