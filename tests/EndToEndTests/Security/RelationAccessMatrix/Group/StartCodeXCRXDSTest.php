<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '02IiNr0GOoKiPMM6tVNYZXaBkkqVJvVjoHbJQ', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '02IiNr0GOoKiPMLomljmyLhQolRrK3QdEEgxx', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '02IiNr0GOoKiPMM0EJLK37XRMnFylE0FG4YHG', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '02IiNr0GOoKiPMMwktnjlFPR38w8pobrXLCSL', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', '02IiNr0GOoKiPMMrBcNFcXfRx1IZ2I7o5CCak', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLCgJ6t5ZjZo0EXyfAUFVs7x', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '02IiNr0GOoKiPMLFKQkMTf3P1jccbVzMDYGuq', '02IiNr0GOoKiPMLeFx00VyBFUjrW9Iz0exnca', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
