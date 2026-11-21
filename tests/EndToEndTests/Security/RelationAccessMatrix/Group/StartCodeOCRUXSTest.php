<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMNKSFPfb62at8UsqOOqec3s', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMECPfTwCq31eACFxhB5Kccv', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '02IiNr0GOoKiPMMIydWATUcTAtkhxArs7VWga', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '02IiNr0GOoKiPMMqW4zr2s68OQDNsS9mGSlnY', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '02IiNr0GOoKiPMMyrCJfvbnt4ewI4XOLhEveg', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '02IiNr0GOoKiPMMof39xiHR76pRfZLABdP7yC', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '02IiNr0GOoKiPMM6C8EsglBTOmdp2mDluqFcD', '02IiNr0GOoKiPMMxlFPEXZCfqeRP7VljhhUD7', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
