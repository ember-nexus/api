<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '02IiNr0GOoKiPMMl0ammaz89QFdZZWcwXjjVg', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '02IiNr0GOoKiPML8y5bTWT97HDjv0qjlODJT6', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '02IiNr0GOoKiPMMmk7iXo42X2sXJOfg9HqaVN', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '02IiNr0GOoKiPMM4lTEsTbjFn3mOEL5Uk92Gz', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '02IiNr0GOoKiPMLEu1HV1tf1w2jtOZVIywaat', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '02IiNr0GOoKiPMMkjbfqvTAwEmZEZlCxwWX2v', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '02IiNr0GOoKiPMMQHYa9tfIfXcU9YVetVyxNb', '02IiNr0GOoKiPMMBSba516GWgzY6XZKPICehj', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
