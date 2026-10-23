<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '8fa5cb40-113b-4b7f-9574-5ce5a153a278', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', 'bcf7620f-61ca-40d2-adec-8193e4f5d738', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', 'e6698dab-e65d-41ee-8546-62b27262def9', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '9ab1c2d2-ef60-4075-be0f-217ddd378b56', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', 'd8f52a3b-5e7b-45e5-b803-ccff78e133ee', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '755fd5f2-3070-4538-954a-7ec14d274fff', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '90915089-9364-4771-89b4-84b83cd50a9f', '508a591e-13f3-4df5-bc4d-5cccf4b176b9', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
