<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Group;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "group" (start nodes: group, end nodes: group). Every data set is
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
            'OCRUXS -> XXXXXX' => ['OCRUXS', 'XXXXXX', 'a0f11774-d648-4f49-91a3-10eb38936402', 'ebccc875-777b-400c-918f-10a15a7395ae', 'cc3738d6-f150-4218-bd55-7d2aebfb7735'],
            'OCRUXS -> OCXUDS' => ['OCRUXS', 'OCXUDS', '8e37ea64-4361-4881-8ad7-18fdefcbb1ba', 'ebccc875-777b-400c-918f-10a15a7395ae', 'b42b3bbf-3c83-4f11-8ecb-051789943944'],
            'OCRUXS -> XCRUDS' => ['OCRUXS', 'XCRUDS', '9803a88a-2a85-4ece-bb8e-5b82953132f7', 'ebccc875-777b-400c-918f-10a15a7395ae', '37178e09-8f8e-4660-8abf-f573093e61f9'],
            'OCRUXS -> XCXUDS' => ['OCRUXS', 'XCXUDS', 'dce383d5-e7e3-4974-b5ca-f433c70daa30', 'ebccc875-777b-400c-918f-10a15a7395ae', '361f350d-36cf-4106-a9d4-0cbdfbb5ab8c'],
            'OCRUXS -> OCRUDS' => ['OCRUXS', 'OCRUDS', 'ee0f6126-2557-4eb4-a27c-b792a9394893', 'ebccc875-777b-400c-918f-10a15a7395ae', '9c7dc32e-fb95-4eca-8fd2-fb595002fa42'],
            'OCRUXS -> OXXXXX' => ['OCRUXS', 'OXXXXX', 'd91dc9d5-7c57-4ae2-9ca6-76c1c90fddcf', 'ebccc875-777b-400c-918f-10a15a7395ae', '227987d1-c052-498e-9ebe-37451a2145c3'],
            'OCRUXS -> XXRXXX' => ['OCRUXS', 'XXRXXX', '7dc6eff7-ac22-41f7-bf70-1982b81db806', 'ebccc875-777b-400c-918f-10a15a7395ae', '2685b21c-acaa-4cf5-913a-8258631a5a03'],
        ];
    }
}
