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
            'OXXXXX -> XXXXXX' => ['OXXXXX', 'XXXXXX', 'e84b81b9-d3f3-422d-ba96-56784f83545f', 'deaaffb0-415f-495b-b688-bc32f1784273', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OXXXXX -> OCXUDS' => ['OXXXXX', 'OCXUDS', '8e1ae22a-cd83-417d-bf82-7199924ac934', 'deaaffb0-415f-495b-b688-bc32f1784273', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OXXXXX -> XCRUDS' => ['OXXXXX', 'XCRUDS', 'cd251663-084b-4635-a6fe-78e8815d13d4', 'deaaffb0-415f-495b-b688-bc32f1784273', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OXXXXX -> XCXUDS' => ['OXXXXX', 'XCXUDS', 'ee2390c0-f374-4c05-9875-eab69e7e2a0f', 'deaaffb0-415f-495b-b688-bc32f1784273', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OXXXXX -> OCRUDS' => ['OXXXXX', 'OCRUDS', 'f6983b43-91b6-4549-b82a-be2f49ad1206', 'deaaffb0-415f-495b-b688-bc32f1784273', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OXXXXX -> OXXXXX' => ['OXXXXX', 'OXXXXX', '506fc568-b0d7-4672-b793-2cfc691a1800', 'deaaffb0-415f-495b-b688-bc32f1784273', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OXXXXX -> XXRXXX' => ['OXXXXX', 'XXRXXX', '768cba85-6a72-4a13-9287-e9dc7d3f35ce', 'deaaffb0-415f-495b-b688-bc32f1784273', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
