<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementPropertyReset\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use App\EventSystem\ElementPropertyReset\EventListener\HasFileElementPropertyResetEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(HasFileElementPropertyResetEventListener::class)]
class HasFileElementPropertyResetEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testAddsHasFilePropertyNameToBeKept(): void
    {
        $element = $this->prophesize(NodeElementInterface::class)->reveal();
        $event = new ElementPropertyResetEvent($element);

        (new HasFileElementPropertyResetEventListener())->onElementPropertyResetEvent($event);

        $this->assertSame(['hasFile'], $event->getPropertyNamesWhichAreKept());
    }
}
