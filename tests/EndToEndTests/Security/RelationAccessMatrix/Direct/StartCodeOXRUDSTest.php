<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '7643db47-1bc7-44a2-9a33-9fa3475c776f', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '1e0defcd-f115-4584-b9a3-5dbcc8765b4a', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', 'ee845fc7-6b63-411d-aab6-79be219d8fac', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '0f468df0-6f2a-475e-ac77-8d0b0f0f9fa6', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '600c8748-b522-4c53-b6b1-dfbf07ac21cb', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', 'd6cb0706-636b-46e6-a050-a2549032974c', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', 'dea25f88-c05d-4d96-b3db-8c30f60aa9ab', '421931fb-1b08-428a-bf4a-ed0aaa389fb3', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
