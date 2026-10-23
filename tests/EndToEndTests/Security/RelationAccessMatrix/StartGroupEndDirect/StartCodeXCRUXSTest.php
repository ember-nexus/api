<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
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
            'XCRUXS -> XXXXXX' => ['XCRUXS', 'XXXXXX', '73aa3567-ffbe-49d4-8352-20767fc46546', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XCRUXS -> OCXUDS' => ['XCRUXS', 'OCXUDS', '07d4ad77-9379-4d98-ad2a-5be60c5b6284', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XCRUXS -> XCRUDS' => ['XCRUXS', 'XCRUDS', '3ab0a624-f367-465e-a3bc-417e08bc18ba', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XCRUXS -> XCXUDS' => ['XCRUXS', 'XCXUDS', '3c2e673c-c867-4fb4-9e5b-88af5418b410', 'a37c6f44-885a-48f1-b39a-7731742ac5be', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XCRUXS -> OCRUDS' => ['XCRUXS', 'OCRUDS', '0f85f992-3233-4171-9e95-632c42ecb61e', 'a37c6f44-885a-48f1-b39a-7731742ac5be', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XCRUXS -> OXXXXX' => ['XCRUXS', 'OXXXXX', '895d1977-abe7-4b61-938c-51ce50b8bd25', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XCRUXS -> XXRXXX' => ['XCRUXS', 'XXRXXX', 'b2a307f6-db03-4194-a262-fceb26b6d6f9', 'a37c6f44-885a-48f1-b39a-7731742ac5be', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
