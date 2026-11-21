<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', '02IiNr0GOoKiPMMRLaLHWg2vpYDMe2sL6B69R', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '02IiNr0GOoKiPMMvXTl8Dd0QUvpTUMMGeGFba', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '02IiNr0GOoKiPMM8VbLZPf4AsRjFNR0k9kDfU', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '02IiNr0GOoKiPMLXJdfA9b7drm8dQOZAhMqRw', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '02IiNr0GOoKiPMMugg8eSqJW3Eys0b3UZ1f7K', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '02IiNr0GOoKiPMM9h7QhkQWLXiCxEuGixj0Nq', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '02IiNr0GOoKiPMMdHEMDCqSj61LHZZxj88FhY', '02IiNr0GOoKiPMMlLLoYDBg5B8NS6YiRmHx0U', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
