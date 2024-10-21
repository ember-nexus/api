<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEventFactory;
use App\Factory\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEventFactory;
use App\Service\ElementFragmentizeService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(ElementFragmentizeService::class)]
class ElementFragmentizeServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildElementFragmentizeService(
        ?EventDispatcherInterface $eventDispatcher,
    ): ElementFragmentizeService {
        $elementFragmentizeService = new ElementFragmentizeService(
            $eventDispatcher,
            new NodeElementFragmentizeEventFactory(),
            new RelationElementFragmentizeEventFactory()
        );

        return $elementFragmentizeService;
    }

    public function testFragmentizeNode(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(NodeElementFragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) {
                $event = $args[0];
                /** @var NodeElementFragmentizeEvent $event */
                $event->getCypherFragment()->addProperty('key-cypher', 'value-cypher');
                $event->getMongoFragment()->addProperty('key-mongo', 'value-mongo');
                $event->getElasticFragment()->addProperty('key-elastic', 'value-elastic');

                return $event;
            });

        $elementFragmentizeService = $this->buildElementFragmentizeService($eventDispatcher->reveal());

        $node = new NodeElement();
        $fragmentGroup = $elementFragmentizeService->fragmentize($node);

        $this->assertSame('value-cypher', $fragmentGroup->getCypherFragment()->getProperty('key-cypher'));
        $this->assertSame('value-mongo', $fragmentGroup->getMongoFragment()->getProperty('key-mongo'));
        $this->assertSame('value-elastic', $fragmentGroup->getElasticFragment()->getProperty('key-elastic'));
    }

    public function testFragmentizeRelation(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(RelationElementFragmentizeEvent::class))
            ->shouldBeCalledOnce()
            ->will(function ($args) {
                $event = $args[0];
                /** @var RelationElementFragmentizeEvent $event */
                $event->getCypherFragment()->addProperty('key-cypher', 'value-cypher');
                $event->getMongoFragment()->addProperty('key-mongo', 'value-mongo');
                $event->getElasticFragment()->addProperty('key-elastic', 'value-elastic');

                return $event;
            });

        $elementFragmentizeService = $this->buildElementFragmentizeService($eventDispatcher->reveal());

        $relation = new RelationElement();
        $fragmentGroup = $elementFragmentizeService->fragmentize($relation);

        $this->assertSame('value-cypher', $fragmentGroup->getCypherFragment()->getProperty('key-cypher'));
        $this->assertSame('value-mongo', $fragmentGroup->getMongoFragment()->getProperty('key-mongo'));
        $this->assertSame('value-elastic', $fragmentGroup->getElasticFragment()->getProperty('key-elastic'));
    }
}
