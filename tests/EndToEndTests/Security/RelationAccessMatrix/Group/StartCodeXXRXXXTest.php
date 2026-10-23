<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '85eae6ce-af10-4076-a97a-95dce911e276', '8051b81a-525c-423d-a7b8-d150679b9f96', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '19350d31-10a7-4671-af71-240d6082247d', '8051b81a-525c-423d-a7b8-d150679b9f96', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '508e8b53-2a11-405a-b415-92bcf2f4ff68', '8051b81a-525c-423d-a7b8-d150679b9f96', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '9111508f-fe57-4793-8bc9-73c3ffeab966', '8051b81a-525c-423d-a7b8-d150679b9f96', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', 'acb0c3bf-2a77-450b-946a-2db8364461e7', '8051b81a-525c-423d-a7b8-d150679b9f96', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '50c2d984-7ca0-436f-9534-04eb25faf063', '8051b81a-525c-423d-a7b8-d150679b9f96', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '2d916704-bdc0-4647-964a-9934d5998b0e', '8051b81a-525c-423d-a7b8-d150679b9f96', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
