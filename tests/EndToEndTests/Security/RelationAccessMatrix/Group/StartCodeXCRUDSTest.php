<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'XCRUDS -> XXXXXX' => ['XCRUDS', 'XXXXXX', '02IiNr0GOoKiPML8ogINMp8TP0PdXmKMTXq0a', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'XCRUDS -> OCXUDS' => ['XCRUDS', 'OCXUDS', '02IiNr0GOoKiPMMZ4sURbTLt3LnfHvTYzzpk3', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'XCRUDS -> XCRUDS' => ['XCRUDS', 'XCRUDS', '02IiNr0GOoKiPMLwyLmDdsYb0J5jmLbYqZQNK', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'XCRUDS -> XCXUDS' => ['XCRUDS', 'XCXUDS', '02IiNr0GOoKiPMMqUDDotuJ8OU8C2asBBSwhD', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'XCRUDS -> OCRUDS' => ['XCRUDS', 'OCRUDS', '02IiNr0GOoKiPMMiQAkkZSC0U2KltBdzj0A6t', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'XCRUDS -> OXXXXX' => ['XCRUDS', 'OXXXXX', '02IiNr0GOoKiPMLl8KA7va63kYg72lvVf0ast', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'XCRUDS -> XXRXXX' => ['XCRUDS', 'XXRXXX', '02IiNr0GOoKiPMLdrOlqTrXVXUqRkUEgZHf2h', '02IiNr0GOoKiPML7EU0WdnkdjL5Fv6sk7qhug', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
