<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use App\Service\ResetElementPropertiesService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(ResetElementPropertiesService::class)]
class ResetElementPropertiesServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testResetElementProperties(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher
            ->dispatch(Argument::type(ElementPropertyResetEvent::class))
            ->will(function ($args) {
                $event = $args[0];
                /** @var ElementPropertyResetEvent $event */
                $event->addPropertyNameToBeKept('test');

                return $event;
            });

        $resetElementPropertiesService = new ResetElementPropertiesService(
            $eventDispatcher->reveal()
        );

        $element = new NodeElement();
        $element->addProperties([
            'test' => 'value',
            'otherKey' => 'otherValue',
        ]);

        $res = $resetElementPropertiesService->resetElementProperties($element);
        $this->assertSame($element, $res);
        $this->assertSame('value', $element->getProperty('test'));
        $this->assertNull($element->getProperty('otherKey'));

        $element = new RelationElement();
        $element->addProperties([
            'test' => 'value',
            'otherKey' => 'otherValue',
        ]);

        $res = $resetElementPropertiesService->resetElementProperties($element);
        $this->assertSame($element, $res);
        $this->assertSame('value', $element->getProperty('test'));
        $this->assertNull($element->getProperty('otherKey'));
    }
}
