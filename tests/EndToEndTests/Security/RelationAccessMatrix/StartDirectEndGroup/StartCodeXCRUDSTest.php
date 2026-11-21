<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '02IiNr0GOoKiPMMqUEoZvsiyHfkUzRgNbdNfF', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '02IiNr0GOoKiPMLhbQYH8AhvCtD4cECN76TbA', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '02IiNr0GOoKiPMLV5uKH1pL1y4wskp1jCX4zq', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '02IiNr0GOoKiPMLzWsSffC4ZvEerK7xXQs0xB', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '02IiNr0GOoKiPMMcucgmAFcHCnKzE9W4utnyB', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLjWL4P1vULuWMvzo1JD2nyH', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '02IiNr0GOoKiPMLUKhFiYpHVhgCU89ShpmKer', '02IiNr0GOoKiPMLmI3Fn2d2eIZoHmK9HXRBxW', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
