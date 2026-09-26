<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\UploadElementPropertyChangeEventListener;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(UploadElementPropertyChangeEventListener::class)]
class UploadElementPropertyChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function createEventListener(): UploadElementPropertyChangeEventListener
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');

        return new UploadElementPropertyChangeEventListener(
            new Client400ForbiddenPropertyExceptionFactory($urlGenerator->reveal())
        );
    }

    public function testElementsWhichAreNotUploadsAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $this->createEventListener()->onElementPropertyChangeEvent(
            new ElementPropertyChangeEvent('Test', null, ['hashState' => 'abc'])
        );
    }

    public function testUploadsWithoutChangedPropertiesAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $this->createEventListener()->onElementPropertyChangeEvent(
            new ElementPropertyChangeEvent('Upload', null, [])
        );
    }

    public function testUploadWithInternalPropertyTriggersException(): void
    {
        $this->expectException(Exception::class);
        $this->createEventListener()->onElementPropertyChangeEvent(
            new ElementPropertyChangeEvent('Upload', null, ['hashState' => 'abc'])
        );
    }

    public function testUploadWithArbitraryPropertyTriggersException(): void
    {
        $this->expectException(Exception::class);
        $this->createEventListener()->onElementPropertyChangeEvent(
            new ElementPropertyChangeEvent('Upload', null, ['name' => 'Test'])
        );
    }
}
