<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '315f83eb-1809-44ff-b062-7a44a38bd15c', '0bd476e6-f235-4905-8308-05a06cd4996c', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '71a579d7-0ba6-42a7-a6c5-e8c704e548bb', '0bd476e6-f235-4905-8308-05a06cd4996c', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '810eeafa-bbb4-4722-aac5-7b96c803f51a', '0bd476e6-f235-4905-8308-05a06cd4996c', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', 'c0c3a54f-db68-4098-b9aa-a9db30c11f7f', '0bd476e6-f235-4905-8308-05a06cd4996c', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '31efd613-b0fb-49ce-bc71-350522a093f3', '0bd476e6-f235-4905-8308-05a06cd4996c', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '4665ae51-04c6-44a2-8a99-403bcdf93d7b', '0bd476e6-f235-4905-8308-05a06cd4996c', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '0f17093d-231b-437b-8c87-8f6ce12c3e82', '0bd476e6-f235-4905-8308-05a06cd4996c', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
