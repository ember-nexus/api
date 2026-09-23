<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementPropertyReset\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use App\EventSystem\ElementPropertyReset\EventListener\FileElementPropertyResetEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(FileElementPropertyResetEventListener::class)]
class FileElementPropertyResetEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testAddsFilePropertyNameToBeKept(): void
    {
        $element = $this->prophesize(NodeElementInterface::class)->reveal();
        $event = new ElementPropertyResetEvent($element);

        (new FileElementPropertyResetEventListener())->onElementPropertyResetEvent($event);

        $this->assertSame(['file'], $event->getPropertyNamesWhichAreKept());
    }
}
