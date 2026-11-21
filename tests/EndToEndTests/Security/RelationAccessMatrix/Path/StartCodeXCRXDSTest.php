<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
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
            'XCRXDS -> XXXXXX' => ['XCRXDS', 'XXXXXX', '02IiNr0GOoKiPMLusCKInOq8FL3dX20hFEfUL', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMLMy9NNhgRmhjpjtrf7gKE1B'],
            'XCRXDS -> OCXUDS' => ['XCRXDS', 'OCXUDS', '02IiNr0GOoKiPMLgM7BTUtNbvWOxOs7lw0Q4b', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMMJljahd3UZfQaO6egBw0zBW'],
            'XCRXDS -> XCRUDS' => ['XCRXDS', 'XCRUDS', '02IiNr0GOoKiPMMV3mHHr9mOqB0LtjE5wNlEg', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMMfPPDWy1NhbxVfd5NVEup4Y'],
            'XCRXDS -> XCXUDS' => ['XCRXDS', 'XCXUDS', '02IiNr0GOoKiPML9jFfsgYXGN86HIOie5KYUl', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMMFM85Q6aSyJcX8ldLJWnopl'],
            'XCRXDS -> OCRUDS' => ['XCRXDS', 'OCRUDS', '02IiNr0GOoKiPMN1K2YpafZ45sFAbTWS6fZYr', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMLeYm0QUVxnHhQMmwgxGmFPU'],
            'XCRXDS -> OXXXXX' => ['XCRXDS', 'OXXXXX', '02IiNr0GOoKiPMLdSqNqRBmpFQnlVQ7c0JQZY', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMLI87PqL4JU8Gm8Ou2jJ0HuN'],
            'XCRXDS -> XXRXXX' => ['XCRXDS', 'XXRXXX', '02IiNr0GOoKiPMM08g9vwSWQGr2ziJFXPAIeS', '02IiNr0GOoKiPMMoPtrim483b0BhqSVVnYTbu', '02IiNr0GOoKiPMLInYOwl7vS2nOhWTQfDbUuL'],
        ];
    }
}
