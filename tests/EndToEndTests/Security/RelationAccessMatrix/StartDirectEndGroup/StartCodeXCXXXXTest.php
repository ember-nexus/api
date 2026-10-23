<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCXXXX -> XXXXXX' => ['XCXXXX', 'XXXXXX', '2aef891a-79cd-474b-ad0b-b8bf3ef83e9a', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCXXXX -> OCXUDS' => ['XCXXXX', 'OCXUDS', '738705f7-6ab3-4d03-98bd-9b6e0e60c6d7', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCXXXX -> XCRUDS' => ['XCXXXX', 'XCRUDS', 'bcdc42bc-1b15-4bc0-8254-13a7e74e4353', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCXXXX -> XCXUDS' => ['XCXXXX', 'XCXUDS', '24cd5cfa-eae7-4f18-ab34-ce04352e7b71', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCXXXX -> OCRUDS' => ['XCXXXX', 'OCRUDS', '93563fb5-ad1a-4724-a315-54c9fc514840', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCXXXX -> OXXXXX' => ['XCXXXX', 'OXXXXX', '4503de7b-c2d8-4320-a45a-7975ab5310c4', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCXXXX -> XXRXXX' => ['XCXXXX', 'XXRXXX', 'c9735fef-f552-4e30-b2e5-865746c990d1', '2d2b5e68-3ba5-40fc-a311-18b59a291ba7', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
