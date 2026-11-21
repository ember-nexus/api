<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFragmentize\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\EventListener\SensitivePropertiesElasticFragmentizeEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

#[Small]
#[CoversClass(SensitivePropertiesElasticFragmentizeEventListener::class)]
class SensitivePropertiesElasticFragmentizeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param list<string> $expectedRemovedProperties
     */
    #[DataProvider('removedPropertiesProvider')]
    public function testSensitivePropertiesAreRemovedFromElasticFragmentOnly(string $label, array $expectedRemovedProperties): void
    {
        $nodeElement = $this->prophesize(NodeElementInterface::class);
        $nodeElement->getLabel()->willReturn($label);

        $cypherFragment = $this->prophesize(NodeInterface::class);
        $cypherFragment->removeProperty(Argument::cetera())->shouldNotBeCalled();
        $mongoFragment = $this->prophesize(MongoDocumentInterface::class);
        $mongoFragment->removeProperty(Argument::cetera())->shouldNotBeCalled();
        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);
        foreach ($expectedRemovedProperties as $propertyName) {
            $elasticFragment->removeProperty($propertyName)->shouldBeCalledOnce()->willReturn($elasticFragment->reveal());
        }

        $event = new NodeElementFragmentizeEvent(
            $nodeElement->reveal(),
            $cypherFragment->reveal(),
            $mongoFragment->reveal(),
            $elasticFragment->reveal(),
        );

        (new SensitivePropertiesElasticFragmentizeEventListener())->onNodeElementFragmentizeEvent($event);
        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function removedPropertiesProvider(): array
    {
        return [
            'user' => ['User', ['_passwordHash']],
            'token' => ['Token', ['hash', '_tokenHash']],
        ];
    }

    public function testOtherLabelsAreNotTouched(): void
    {
        $nodeElement = $this->prophesize(NodeElementInterface::class);
        // a file's `hash` property is unrelated to token hashes and must stay searchable
        $nodeElement->getLabel()->willReturn('Data');

        $elasticFragment = $this->prophesize(ElasticDocumentInterface::class);
        $elasticFragment->removeProperty('hash')->shouldNotBeCalled();
        $elasticFragment->removeProperty('_passwordHash')->shouldNotBeCalled();

        $event = new NodeElementFragmentizeEvent(
            $nodeElement->reveal(),
            $this->prophesize(NodeInterface::class)->reveal(),
            $this->prophesize(MongoDocumentInterface::class)->reveal(),
            $elasticFragment->reveal(),
        );

        (new SensitivePropertiesElasticFragmentizeEventListener())->onNodeElementFragmentizeEvent($event);
        $this->addToAssertionCount(1);
    }
}
