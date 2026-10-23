<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRUXSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', 'aa583bf7-e405-439e-ba4b-e4165d9f8085', '64577171-49ff-4e7a-934d-268e20b0bb7b', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', 'ad6fdfb5-6948-4825-bf2d-4c2dec6cb8d6', '64577171-49ff-4e7a-934d-268e20b0bb7b', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', 'ce97f8f4-a664-4385-88ba-fd0205a13cd8', '64577171-49ff-4e7a-934d-268e20b0bb7b', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', '81597f04-31b0-49d1-816e-61538368dc34', '64577171-49ff-4e7a-934d-268e20b0bb7b', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', '2e498ea2-d1e7-4c37-8a6a-1d2e243ebfaf', '64577171-49ff-4e7a-934d-268e20b0bb7b', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', '5a910b4e-f12b-40d4-bf15-d4d47339139b', '64577171-49ff-4e7a-934d-268e20b0bb7b', '16feb737-802f-4dab-b88d-803538f18103'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', 'b407f070-e752-4528-8146-5b17956e089f', '64577171-49ff-4e7a-934d-268e20b0bb7b', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
