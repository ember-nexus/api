<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXXXX -> XXXXXX' => ['XXXXXX', 'XXXXXX', '6b2250b0-f445-4b9d-9f04-cb9bf925514e', '5e25c1ab-899b-455e-8101-36cb709c591b', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'XXXXXX -> OCXUDS' => ['XXXXXX', 'OCXUDS', 'b5775fb6-ed2a-473c-9044-bbab7a891b83', '5e25c1ab-899b-455e-8101-36cb709c591b', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'XXXXXX -> XCRUDS' => ['XXXXXX', 'XCRUDS', 'c4b43179-73af-438e-9771-5b7acebf7bd2', '5e25c1ab-899b-455e-8101-36cb709c591b', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'XXXXXX -> XCXUDS' => ['XXXXXX', 'XCXUDS', '833c78bb-d705-4677-9277-c07e80101b15', '5e25c1ab-899b-455e-8101-36cb709c591b', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'XXXXXX -> OCRUDS' => ['XXXXXX', 'OCRUDS', '5c6a7a33-f236-4d0b-b26a-584af9e397f9', '5e25c1ab-899b-455e-8101-36cb709c591b', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'XXXXXX -> OXXXXX' => ['XXXXXX', 'OXXXXX', '7240c9c1-dcac-4825-9f99-78b35838ee34', '5e25c1ab-899b-455e-8101-36cb709c591b', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'XXXXXX -> XXRXXX' => ['XXXXXX', 'XXRXXX', '793aee24-f291-45f0-84b9-f1f1e7b2c512', '5e25c1ab-899b-455e-8101-36cb709c591b', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
