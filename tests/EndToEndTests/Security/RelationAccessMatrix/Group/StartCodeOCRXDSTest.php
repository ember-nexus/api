<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', '02IiNr0GOoKiPMLs6Sk04A7kM7J8Fnekxcp3q', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '02IiNr0GOoKiPMN2ZJyWZF6VVn96TLit2IPIP', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '02IiNr0GOoKiPMLOhdqjyw6jfDHYFOdbp4AhM', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '02IiNr0GOoKiPMMkJ8NvcwsGnxKtaBTqOVRoT', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '02IiNr0GOoKiPMLmkNQElwxRovhfWNnrASBVF', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLDmDw4bozTpct1d89ObN3xx', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '02IiNr0GOoKiPMN6LOErgI2TgNOa0nOVdpnpU', '02IiNr0GOoKiPMMfLbZdU7W18ubkMLLt6vBvh', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
