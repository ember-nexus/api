<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXCXUDSTest extends BaseRelationAccessMatrixTestCase
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
            'XCXUDS -> XXXXXX' => ['XCXUDS', 'XXXXXX', '02IiNr0GOoKiPMM0I3JNlZoFBxVnojjUmH7lT', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCXUDS -> OCXUDS' => ['XCXUDS', 'OCXUDS', '02IiNr0GOoKiPMMv1M8MODKveCrY3hzGHo0rq', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCXUDS -> XCRUDS' => ['XCXUDS', 'XCRUDS', '02IiNr0GOoKiPMN1Aa1PtZ6inHzgVq2huLhnA', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCXUDS -> XCXUDS' => ['XCXUDS', 'XCXUDS', '02IiNr0GOoKiPMMnLtEnbeFolw0CMNR0MhiMd', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCXUDS -> OCRUDS' => ['XCXUDS', 'OCRUDS', '02IiNr0GOoKiPMLxbycC4cAkXQloJJQvgUZAT', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCXUDS -> OXXXXX' => ['XCXUDS', 'OXXXXX', '02IiNr0GOoKiPMMVF7QQ7oAWcxMXwyEgVC3Ff', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCXUDS -> XXRXXX' => ['XCXUDS', 'XXRXXX', '02IiNr0GOoKiPMLkJNrx2NBbmp2Fs8NdFNgoA', '02IiNr0GOoKiPMLkDlbraNDQPJt5yfZIwwYHm', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
