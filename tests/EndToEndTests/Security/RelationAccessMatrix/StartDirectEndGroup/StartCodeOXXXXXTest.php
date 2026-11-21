<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\StartDirectEndGroup;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "startDirectEndGroup" (start nodes: direct, end nodes: group). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOXXXXXTest extends BaseRelationAccessMatrixTestCase
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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', '02IiNr0GOoKiPMMw3ZFiAbshQXlG8RqTwKGqN', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMiOHjuRd7y77dQ3fgRSC0QG'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '02IiNr0GOoKiPMME8f2Wd4K62T1aE3E13RBVm', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMMWfpWiqYSw5FejFsmoqqbXm'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', '02IiNr0GOoKiPMMiqZeIAkkKVrFt9HUGGlAQR', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLXlyCQJ1zgJVyHuMOqTyQdE'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', '02IiNr0GOoKiPMMyustz3QOjYCPVCzebv5hT4', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLXHporIPXjIZGrLM7j6wZ3G'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', '02IiNr0GOoKiPMN31ezy8n1KOZpFYHQQ7hq7u', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMML8gagHwa5Ve2xUH86ZP65F'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '02IiNr0GOoKiPMLk6FqYTf29qbdOPmevDvtYk', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLNjHdOQx1PZ4VgFV4ztPmMl'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '02IiNr0GOoKiPMM2fHm9dVv9bydKTrE6cASve', '02IiNr0GOoKiPMMrMyCa4YaRDbyOEdZ68opVX', '02IiNr0GOoKiPMLPhuNoccr1OHlF74EFowe1C'],
        ];
    }
}
