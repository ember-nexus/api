<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '13225838-8faf-4d9b-a15c-47fe341dd11f', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', 'c6adb777-6459-4e32-b4f1-e09b032bd1ec', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '0a189092-8c96-44b0-a246-e79c9761f538', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '38c06317-cfc1-49fe-aced-779f495a1280', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '3a7713bd-5001-4c27-a173-5107d93b13fc', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '6eb82ec7-b502-4ee7-9767-086ef9357f31', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '63390806-c43b-44b4-99fc-813744c7f615', 'a14b110d-fe70-49f8-8eb8-56936dc6b602', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
