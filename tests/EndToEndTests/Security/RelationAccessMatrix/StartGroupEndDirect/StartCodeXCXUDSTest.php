<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '1620a36f-2d9d-413b-a72c-171e584763e4', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', 'b0ff246e-33eb-4f9e-b244-570c1b5bc45a', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', 'c8e9eceb-1cf6-4d22-9cc0-530f4a627fb6', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', 'f6e2ff0d-facf-4cd9-869e-6633f55eb242', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '99a7c735-3b28-4a8d-91de-08d876987491', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '66ace128-3517-4e2e-9d8a-6d7943895965', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '6804799a-7be8-4f9d-af10-5933f56ae4e0', '50aaf183-7a5c-4b87-9ee1-2a4b8199668a', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
