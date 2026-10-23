<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '98470f31-03d1-48d0-ba70-2d220794305a', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '2cd22ced-9fd2-4d98-a4c4-c1394261eb96', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '6bc8c696-66fd-4b3d-b0f2-e0055e91ff85', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '07d43768-7a12-485c-a429-7c20d93bc808', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '1fc45e41-43ab-489b-9bd9-71bc3a12b68a', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '15024387-90fa-4570-b6b8-bf05a92adc05', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '98252595-7030-49e4-8563-6eaf0128797b'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '52ef86e3-9805-4335-9fd1-f4a46bdde3fe', 'ed1619f6-fb9a-41b4-8eac-293f6f138152', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
