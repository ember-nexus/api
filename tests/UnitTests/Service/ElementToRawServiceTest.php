<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\EventSystem\ElementPropertyReturn\EventListener\TokenElementPropertyReturnEventListener;
use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use App\Service\ElementToRawService;
use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[Small]
#[CoversClass(ElementToRawService::class)]
class ElementToRawServiceTest extends TestCase
{
    private function createToken(): NodeElement
    {
        $token = new NodeElement();
        $token->setLabel('Token');
        $token->addProperty('hash', 'secret-hash');
        $token->addProperty('_tokenHash', 'secret-hash');
        $token->addProperty('token', 'secret-token');
        $token->addProperty('name', 'reference token');

        return $token;
    }

    private function createDispatcherWithTokenListener(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            ElementPropertyReturnEvent::class,
            (new TokenElementPropertyReturnEventListener())->onElementPropertyReturnEvent(...)
        );
        $dispatcher->addListener(
            NormalizedValueToRawValueEvent::class,
            static function (NormalizedValueToRawValueEvent $event): void {
                $event->setRawValue($event->getNormalizedValue());
            }
        );

        return $dispatcher;
    }

    public function testElementToRawWithBlacklistAppliedHidesTokenHash(): void
    {
        $service = new ElementToRawService($this->createDispatcherWithTokenListener());

        $rawData = $service->elementToRaw($this->createToken());

        $this->assertArrayNotHasKey('hash', $rawData['data']);
        $this->assertArrayNotHasKey('_tokenHash', $rawData['data']);
        $this->assertArrayNotHasKey('token', $rawData['data']);
        $this->assertSame('reference token', $rawData['data']['name']);
    }

    public function testElementToRawWithBlacklistDisabledKeepsTokenHash(): void
    {
        $service = new ElementToRawService($this->createDispatcherWithTokenListener());

        $rawData = $service->elementToRaw($this->createToken(), false);

        $this->assertArrayHasKey('hash', $rawData['data']);
        $this->assertSame('secret-hash', $rawData['data']['hash']);
        $this->assertArrayHasKey('_tokenHash', $rawData['data']);
        $this->assertArrayHasKey('token', $rawData['data']);
        $this->assertSame('reference token', $rawData['data']['name']);
    }

    public function testElementToRawWithBlacklistDisabledSkipsEventDispatchEntirely(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcherWasCalled = false;
        $dispatcher->addListener(
            ElementPropertyReturnEvent::class,
            function () use (&$dispatcherWasCalled): void {
                $dispatcherWasCalled = true;
            }
        );
        $dispatcher->addListener(
            NormalizedValueToRawValueEvent::class,
            static function (NormalizedValueToRawValueEvent $event): void {
                $event->setRawValue($event->getNormalizedValue());
            }
        );

        $service = new ElementToRawService($dispatcher);
        $service->elementToRaw($this->createToken(), false);

        $this->assertFalse($dispatcherWasCalled);
    }
}
