<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Path;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "path" (start nodes: path, end nodes: path). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeOCRXDSTest extends BaseRelationAccessMatrixTestCase
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
            'OCRXDS -> XXXXXX' => ['OCRXDS', 'XXXXXX', 'eb47b403-d708-4f51-bf0d-953649a64298', '18ef9f33-c070-4623-8b63-ccc00088f407', '20ef1c37-902c-45a3-a5c5-b82802dbb8b1'],
            'OCRXDS -> OCXUDS' => ['OCRXDS', 'OCXUDS', '3699c1ce-1757-4d79-9d61-b95bd1af1321', '18ef9f33-c070-4623-8b63-ccc00088f407', '99a8a19c-afc2-4775-9af8-31fce21ab405'],
            'OCRXDS -> XCRUDS' => ['OCRXDS', 'XCRUDS', '741c5c1b-d607-41d6-8613-0fb1a5cb4d65', '18ef9f33-c070-4623-8b63-ccc00088f407', 'c61d2fc3-d9a2-4bd0-958e-c3878b853da9'],
            'OCRXDS -> XCXUDS' => ['OCRXDS', 'XCXUDS', '1b2f607d-826f-4279-82d0-229c229a76ab', '18ef9f33-c070-4623-8b63-ccc00088f407', '90997813-b0e8-40c3-b8c3-1780799e1374'],
            'OCRXDS -> OCRUDS' => ['OCRXDS', 'OCRUDS', '563758da-4882-4056-98de-b1af12d52348', '18ef9f33-c070-4623-8b63-ccc00088f407', '4508aff9-1210-4624-aafd-d1b61c71fef4'],
            'OCRXDS -> OXXXXX' => ['OCRXDS', 'OXXXXX', '656f96ca-3e23-4758-b0bc-9f194db8c831', '18ef9f33-c070-4623-8b63-ccc00088f407', '16feb737-802f-4dab-b88d-803538f18103'],
            'OCRXDS -> XXRXXX' => ['OCRXDS', 'XXRXXX', '965a35c1-762f-4ea6-97ec-308aa9b4c05d', '18ef9f33-c070-4623-8b63-ccc00088f407', '18541ccf-8b24-4b18-bae3-ed06793508be'],
        ];
    }
}
