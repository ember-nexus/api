<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '88fabeb7-ace9-4c20-9f8b-c8cf753a5ce6', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '4f21d737-3bd5-44c8-9982-b1d309849dcb', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '1ca087b1-2541-4264-ba3a-7951a9300fbc', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', 'd667174b-7c85-4872-b046-b65cdaa33e18', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', 'b4b79c2d-c995-4733-ad24-1b9f44380d02', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '41f0e66a-2596-4ea3-b630-ca070fde9d28', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', 'd24b8084-eca4-4b91-b777-f5aa3c9596a0', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
