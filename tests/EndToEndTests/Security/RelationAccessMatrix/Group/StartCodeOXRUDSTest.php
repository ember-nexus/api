<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'OXRUDS -> XXXXXX' => ['OXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLNKhvWHRmNetVaBDryF8142', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OXRUDS -> OCXUDS' => ['OXRUDS', 'OCXUDS', '02IiNr0GOoKiPMMWWPM1N31kPDGAMhIXc8KZ5', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OXRUDS -> XCRUDS' => ['OXRUDS', 'XCRUDS', '02IiNr0GOoKiPMLeJcX4vcHCZSPhbV6Q4DO4U', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OXRUDS -> XCXUDS' => ['OXRUDS', 'XCXUDS', '02IiNr0GOoKiPMMK0qtS8ECJTdJTFqCu9mdxq', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OXRUDS -> OCRUDS' => ['OXRUDS', 'OCRUDS', '02IiNr0GOoKiPMLPpTzzPq4JbvY2ceCTBlfOv', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OXRUDS -> OXXXXX' => ['OXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLqa6HqRjlvq0GVlSCOkMhwN', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OXRUDS -> XXRXXX' => ['OXRUDS', 'XXRXXX', '02IiNr0GOoKiPMMXyu7lKWcXbLwyBiGvh2yFb', '02IiNr0GOoKiPMM1vvel76ZkO6cWT5823nEmM', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
