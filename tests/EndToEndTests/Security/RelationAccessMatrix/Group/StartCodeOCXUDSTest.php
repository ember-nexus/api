<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OCXUDS -> XXXXXX' => ['OCXUDS', 'XXXXXX', '02IiNr0GOoKiPMLUyQXk7Io1k31GKwx8tazni', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OCXUDS -> OCXUDS' => ['OCXUDS', 'OCXUDS', '02IiNr0GOoKiPMM0I33lPOaNy3MTuAn8Byzn3', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OCXUDS -> XCRUDS' => ['OCXUDS', 'XCRUDS', '02IiNr0GOoKiPMM7mJUGQ640slp7GbIYDSgYH', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OCXUDS -> XCXUDS' => ['OCXUDS', 'XCXUDS', '02IiNr0GOoKiPMMcowVdXmzhXJcWm0sHtXGBs', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OCXUDS -> OCRUDS' => ['OCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLVFPcYRkPYHLBbM4zQJScFt', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OCXUDS -> OXXXXX' => ['OCXUDS', 'OXXXXX', '02IiNr0GOoKiPMLfEGSm5u1zjfXx9YKyuFV7U', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OCXUDS -> XXRXXX' => ['OCXUDS', 'XXRXXX', '02IiNr0GOoKiPMLEIIsBrUG3DTRo9e4ZLZYDX', '02IiNr0GOoKiPMLCiA63zekG40bf42HJXAZCB', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
