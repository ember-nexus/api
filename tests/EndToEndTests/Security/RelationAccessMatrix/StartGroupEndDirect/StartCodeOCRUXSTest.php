<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartGroupEndDirect;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startGroupEndDirect" (start nodes: group, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '49da601f-48fe-465b-bb92-6ec864734965', 'ebccc875-777b-400c-918f-10a15a7395ae', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '47781058-92c3-4fb5-bcd1-2dc175564d02', 'ebccc875-777b-400c-918f-10a15a7395ae', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '6f39e9e1-1958-4f72-a58a-768412cf18f5', 'ebccc875-777b-400c-918f-10a15a7395ae', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', 'ca1fe964-9b05-420a-8210-4579b3ccd2f2', 'ebccc875-777b-400c-918f-10a15a7395ae', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '2fde06ff-cbcc-4756-a396-8f3b4121d784', 'ebccc875-777b-400c-918f-10a15a7395ae', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '4e482f3d-0437-4e62-9314-35f76d163377', 'ebccc875-777b-400c-918f-10a15a7395ae', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '66431536-9cdd-4ce6-8740-e92b905919a0', 'ebccc875-777b-400c-918f-10a15a7395ae', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
