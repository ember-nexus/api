<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
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
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '02IiNr0GOoKiPMN1csSiuriJQlnn9grVEwvqK', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '02IiNr0GOoKiPMM0Z0zBOFAC93chCkDx8yNyc', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', '02IiNr0GOoKiPMM2Xg0N1MXaVYvNGtOvFxyDv', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '02IiNr0GOoKiPMLqwmtVn7sC7M77OvZUhC98e', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '02IiNr0GOoKiPMMlNDtaN5xPOn3xcjBRH7aLQ', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '02IiNr0GOoKiPMM0rvlEq9z2MhpkgNx2dWm4V', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', '02IiNr0GOoKiPMMr9lWgzfeivEfcOeTteqyxE', '02IiNr0GOoKiPMLNDFZkn5uN7dQrrl9rySW1j', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
