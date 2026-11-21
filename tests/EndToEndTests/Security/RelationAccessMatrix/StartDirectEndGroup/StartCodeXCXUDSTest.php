<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '02IiNr0GOoKiPMLD8dpnaAxjvclGXBCR4diwn', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '02IiNr0GOoKiPMMp9F8Wsac6N17QT2vS7qjyO', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '02IiNr0GOoKiPMMTguNg8nTIfYwF6bir5hovB', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '02IiNr0GOoKiPMLyeC780dmhnFwv80lJ7onq0', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLaIczI0HPZa205QCtiXpnkF', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '02IiNr0GOoKiPMM3lAQ0bdvAfFLL479HXZZBE', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '02IiNr0GOoKiPMN1kQhLxM6j5aZFpW9M9dc40', '02IiNr0GOoKiPMLSM4AjOXbH5wXFfGD15juvy', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
