<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', 'd19af49f-0a9b-416f-bcd8-de8842fd8f99', '889a9717-8dbf-4384-b146-7afb5b399705', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '0427e260-10ae-45ec-99ba-e0f520e33677', '889a9717-8dbf-4384-b146-7afb5b399705', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', 'd52762fa-3406-498b-afa1-150ce9d21bd9', '889a9717-8dbf-4384-b146-7afb5b399705', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '7ada8644-6b02-4fd2-bd93-eee59d5ca9c2', '889a9717-8dbf-4384-b146-7afb5b399705', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '1053e386-a0f6-433b-8537-2d03788a3d7d', '889a9717-8dbf-4384-b146-7afb5b399705', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', 'd10a8f52-acbd-477c-8bd6-2e804a702217', '889a9717-8dbf-4384-b146-7afb5b399705', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', 'a70a0457-7907-48ed-8c53-7896c475403d', '889a9717-8dbf-4384-b146-7afb5b399705', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
