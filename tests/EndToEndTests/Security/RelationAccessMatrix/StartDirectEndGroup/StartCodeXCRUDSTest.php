<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', 'dcd9aa2d-aca6-40a4-8f46-3e232ccfff22', '54e6ee03-137f-4711-8f08-c6af4bd53f57', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '4b42ceed-bf29-496e-827a-deb0a68aedd7', '54e6ee03-137f-4711-8f08-c6af4bd53f57', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '31965ab8-fae5-41c1-b73b-a5095b1eaf73', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '7015ef6c-79db-49c5-aa64-b1356311e615', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', 'c0f64705-9c9a-4086-894c-6cc1114b92d0', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '4f373029-b39e-4b76-98d4-3c585bafae83', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '300165c1-cd1c-4cdf-b46a-656e24ee5dd4', '54e6ee03-137f-4711-8f08-c6af4bd53f57', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
