<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', 'f3baa984-72c9-494e-9d24-aadde1264694', '216fae63-4d69-4681-8540-ea2e0011442b', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '723346f6-1eb1-4809-b6f4-0bef37cfb646', '216fae63-4d69-4681-8540-ea2e0011442b', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '76446913-5b7a-47a9-8a99-518de0ac7cb7', '216fae63-4d69-4681-8540-ea2e0011442b', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '5e7ae687-264e-4703-b8fa-82183cbb0c78', '216fae63-4d69-4681-8540-ea2e0011442b', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', 'd259357d-8bee-48e7-8753-b8ef1db53b21', '216fae63-4d69-4681-8540-ea2e0011442b', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '72d98479-df8e-48c6-8200-a60bbd21ed21', '216fae63-4d69-4681-8540-ea2e0011442b', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', 'de3b974f-89ba-469a-a495-944634eecb22', '216fae63-4d69-4681-8540-ea2e0011442b', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
