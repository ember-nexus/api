<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRXXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXRXXX -> XXXXXX' => ['XXRXXX', 'XXXXXX', '02IiNr0GOoKiPMMA9T3MK99umxROMINWR8iJB', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXRXXX -> OCXUDS' => ['XXRXXX', 'OCXUDS', '02IiNr0GOoKiPMLJDyitMQKew5TtgXbO08u4D', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXRXXX -> XCRUDS' => ['XXRXXX', 'XCRUDS', '02IiNr0GOoKiPMLkA1GqByEM6h9se3FxthOVz', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXRXXX -> XCXUDS' => ['XXRXXX', 'XCXUDS', '02IiNr0GOoKiPMMFbAIZHrGTc722emFACGXDg', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXRXXX -> OCRUDS' => ['XXRXXX', 'OCRUDS', '02IiNr0GOoKiPMMT3CeiAXcLFbwTVYYhAQ3nZ', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXRXXX -> OXXXXX' => ['XXRXXX', 'OXXXXX', '02IiNr0GOoKiPMLkHTtaEAIdjZievFOCu8luz', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXRXXX -> XXRXXX' => ['XXRXXX', 'XXRXXX', '02IiNr0GOoKiPMLT96YPWanPbOc4CHZVQCv8t', '02IiNr0GOoKiPMM7RSXWTTOTE3WvaJcck7Rkh', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
