<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '4036515b-818b-475b-b3d9-7ca3ef0075ef', 'deaaffb0-415f-495b-b688-bc32f1784273', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '5004abc5-ccd5-4cc0-a18a-6ec69c98ca54', 'deaaffb0-415f-495b-b688-bc32f1784273', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '96dd7ed4-7648-4cd6-a312-ee1dd1501d92', 'deaaffb0-415f-495b-b688-bc32f1784273', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', 'be0a684f-2ea9-4a9b-82a6-7fb9057e06a3', 'deaaffb0-415f-495b-b688-bc32f1784273', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '00687250-1928-4e11-9a0b-b8ecd7633ae9', 'deaaffb0-415f-495b-b688-bc32f1784273', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', 'bf4b5af7-5a1a-4d93-80c5-89d6e59b93cd', 'deaaffb0-415f-495b-b688-bc32f1784273', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', 'ea2c13c0-b76c-4906-a2d8-ff4062791abb', 'deaaffb0-415f-495b-b688-bc32f1784273', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
