<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUDS -> XXXXXX' => ['OCRUDS', 'XXXXXX', '02IiNr0GOoKiPMMTPwlHb1ySeeNPKzlyhZMEA', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCRUDS -> OCXUDS' => ['OCRUDS', 'OCXUDS', '02IiNr0GOoKiPMN5uxVtKnxHriJcPhRTw7d6S', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCRUDS -> XCRUDS' => ['OCRUDS', 'XCRUDS', '02IiNr0GOoKiPMMKFvKxkJ2Lrz0Kg33gn3ha7', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCRUDS -> XCXUDS' => ['OCRUDS', 'XCXUDS', '02IiNr0GOoKiPMMvziKyFVBM7Avi1f0J3JRak', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCRUDS -> OCRUDS' => ['OCRUDS', 'OCRUDS', '02IiNr0GOoKiPMN6P5H9OPqksg15CNoOPqF7f', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCRUDS -> OXXXXX' => ['OCRUDS', 'OXXXXX', '02IiNr0GOoKiPMMcbjrsvP0ZgjiASq5G9T4TR', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCRUDS -> XXRXXX' => ['OCRUDS', 'XXRXXX', '02IiNr0GOoKiPMMX86aqg13YzRePyVZJLMU4Z', '02IiNr0GOoKiPMMW45lwnLKbalYoZEaS6UjNi', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
