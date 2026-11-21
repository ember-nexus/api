<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XXRUDS -> XXXXXX' => ['XXRUDS', 'XXXXXX', '02IiNr0GOoKiPMLrpRasluevVU8lK66OpcyBw', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXRUDS -> OCXUDS' => ['XXRUDS', 'OCXUDS', '02IiNr0GOoKiPMMNGjVUItuHhDl3h8M2TPjym', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXRUDS -> XCRUDS' => ['XXRUDS', 'XCRUDS', '02IiNr0GOoKiPMMuq4T9faXFeW5mYJWA5l4sT', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXRUDS -> XCXUDS' => ['XXRUDS', 'XCXUDS', '02IiNr0GOoKiPMMrUVaQku8LeitKq8MZPDgMf', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXRUDS -> OCRUDS' => ['XXRUDS', 'OCRUDS', '02IiNr0GOoKiPMMAOU9rRfmTRYfCjzeDwZIHv', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXRUDS -> OXXXXX' => ['XXRUDS', 'OXXXXX', '02IiNr0GOoKiPMLc7qMEgEZSTFP3UCFYeYLgq', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXRUDS -> XXRXXX' => ['XXRUDS', 'XXRXXX', '02IiNr0GOoKiPMM06iZxyPbThWdpYMY3rijVt', '02IiNr0GOoKiPMLvTrwcVnZLYzw54a4HIiXRs', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
