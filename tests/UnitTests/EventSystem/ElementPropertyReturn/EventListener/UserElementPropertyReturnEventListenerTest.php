<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\ElementPropertyReturn\EventListener;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\EventSystem\ElementPropertyReturn\EventListener\UserElementPropertyReturnEventListener;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;

class UserElementPropertyReturnEventListenerTest extends TestCase
{
    public function testEventListenerWithNonUserElements(): void
    {
        $eventListener = new UserElementPropertyReturnEventListener();

        $relationEvent = new ElementPropertyReturnEvent(new RelationElement());
        $this->assertEmpty($relationEvent->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($relationEvent);
        $this->assertEmpty($relationEvent->getBlockedProperties());

        $node = new NodeElement();
        $node->setLabel('Data');
        $nodeEvent = new ElementPropertyReturnEvent($node);
        $this->assertEmpty($nodeEvent->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($nodeEvent);
        $this->assertEmpty($nodeEvent->getBlockedProperties());
    }

    public function testEventListenerWithUser(): void
    {
        $eventListener = new UserElementPropertyReturnEventListener();

        $user = new NodeElement();
        $user->setLabel('User');
        $userEvent = new ElementPropertyReturnEvent($user);
        $this->assertEmpty($userEvent->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($userEvent);
        $this->assertCount(1, $userEvent->getBlockedProperties());
        $this->assertContains('_passwordHash', $userEvent->getBlockedProperties());
    }
}
