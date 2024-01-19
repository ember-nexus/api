<?php

namespace App\tests\UnitTests\EventSystem\ElementPropertyReturn\EventListener;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\EventSystem\ElementPropertyReturn\EventListener\TokenElementPropertyReturnEventListener;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;

class TokenElementPropertyReturnEventListenerTest extends TestCase
{
    public function testEventListenerWithNonTokenElements(): void
    {
        $eventListener = new TokenElementPropertyReturnEventListener();

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

    public function testEventListenerWithToken(): void
    {
        $eventListener = new TokenElementPropertyReturnEventListener();

        $token = new NodeElement();
        $token->setLabel('Token');
        $tokenEvent = new ElementPropertyReturnEvent($token);
        $this->assertEmpty($tokenEvent->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($tokenEvent);
        $this->assertCount(3, $tokenEvent->getBlockedProperties());
        $this->assertContains('token', $tokenEvent->getBlockedProperties());
        $this->assertContains('_tokenHash', $tokenEvent->getBlockedProperties());
        $this->assertContains('hash', $tokenEvent->getBlockedProperties());
    }
}
