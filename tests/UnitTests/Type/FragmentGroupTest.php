<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\FragmentGroup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

#[Small]
#[CoversClass(FragmentGroup::class)]
class FragmentGroupTest extends TestCase
{
    public function testFragmentGroup(): void
    {
        $cypherFragment1 = new Node();
        $mongoFragment1 = new MongoDocument();
        $elasticFragment1 = new ElasticDocument();
        $fileFragment1 = null;

        $fragmentGroup = new FragmentGroup(
            $cypherFragment1,
            $mongoFragment1,
            $elasticFragment1,
            $fileFragment1
        );

        $this->assertSame($cypherFragment1, $fragmentGroup->getCypherFragment());
        $this->assertSame($mongoFragment1, $fragmentGroup->getMongoFragment());
        $this->assertSame($elasticFragment1, $fragmentGroup->getElasticFragment());
        $this->assertSame($fileFragment1, $fragmentGroup->getFileFragment());

        $cypherFragment2 = new Node();
        $mongoFragment2 = new MongoDocument();
        $elasticFragment2 = new ElasticDocument();
        $fileFragment2 = 'somethingElse';

        $fragmentGroup->setCypherFragment($cypherFragment2);
        $fragmentGroup->setMongoFragment($mongoFragment2);
        $fragmentGroup->setElasticFragment($elasticFragment2);
        $fragmentGroup->setFileFragment($fileFragment2);

        $this->assertSame($cypherFragment2, $fragmentGroup->getCypherFragment());
        $this->assertSame($mongoFragment2, $fragmentGroup->getMongoFragment());
        $this->assertSame($elasticFragment2, $fragmentGroup->getElasticFragment());
        $this->assertSame($fileFragment2, $fragmentGroup->getFileFragment());
    }
}
