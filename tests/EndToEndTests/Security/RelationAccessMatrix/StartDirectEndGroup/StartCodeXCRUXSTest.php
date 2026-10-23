<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '49b1d03d-c774-4529-9e43-f2c15bad4e76', 'e196e9ab-9031-40cc-bde5-270124941df4', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', 'bc062d46-e8a3-4b8b-a3e5-86ad89074a74', 'e196e9ab-9031-40cc-bde5-270124941df4', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '528fcd4a-0811-46cd-adbb-fc78d73b2b8b', 'e196e9ab-9031-40cc-bde5-270124941df4', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '0f836160-ccb0-4803-b56b-ee51bf6601e6', 'e196e9ab-9031-40cc-bde5-270124941df4', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', 'aa3ef711-6a92-4fe1-82de-a3edf8d84e39', 'e196e9ab-9031-40cc-bde5-270124941df4', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '2c2a191c-1689-4c63-a4fc-df8d20c9c91e', 'e196e9ab-9031-40cc-bde5-270124941df4', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', 'd7674b79-4a0a-4da4-a431-1548e43a5a53', 'e196e9ab-9031-40cc-bde5-270124941df4', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
