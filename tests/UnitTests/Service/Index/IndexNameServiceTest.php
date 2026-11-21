<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Index;

use App\Service\Index\IndexNameService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(IndexNameService::class)]
class IndexNameServiceTest extends TestCase
{
    public function testNodeAndRelationNames(): void
    {
        $service = new IndexNameService();

        $this->assertSame('ember_nexus_node_id_data_'.substr(hash('sha256', 'Data'), 0, 8), $service->getNodeIndexName('Data'));
        $this->assertSame('ember_nexus_relation_id_has_read_access_'.substr(hash('sha256', 'HAS_READ_ACCESS'), 0, 8), $service->getRelationIndexName('HAS_READ_ACCESS'));
    }

    public function testNamesAreUniqueForTypesDifferingOnlyInCase(): void
    {
        $service = new IndexNameService();

        $this->assertNotSame($service->getNodeIndexName('Data'), $service->getNodeIndexName('DATA'));
    }

    public function testSlugIsLimitedAndSanitized(): void
    {
        $name = (new IndexNameService())->getNodeIndexName(str_repeat('a b', 40).'`;');

        $this->assertMatchesRegularExpression('/^ember_nexus_node_id_[a-z0-9_]{1,40}_[0-9a-f]{8}$/', $name);
    }

    public function testIsManagedIndexName(): void
    {
        $service = new IndexNameService();

        $this->assertTrue($service->isManagedIndexName('ember_nexus_node_id_x_00000000'));
        $this->assertFalse($service->isManagedIndexName('some_other_index'));
    }
}
