<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Service\RawToElementService;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(RawToElementService::class)]
class RawToElementServiceTest extends TestCase
{
    private function buildService(): RawToElementService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://mock.dev/123');

        return new RawToElementService(
            new EventDispatcher(),
            new Client400BadContentExceptionFactory($urlGenerator),
            new Client400MissingPropertyExceptionFactory($urlGenerator),
        );
    }

    public function testBase62AndHexIdsAreAccepted(): void
    {
        $uuid = Uuid::generate();

        $fromBase62 = $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toString()]);
        $fromHex = $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toHex()]);

        $this->assertSame($uuid->toString(), $fromBase62->getId()?->toString());
        $this->assertSame($uuid->toString(), $fromHex->getId()?->toString());
    }

    public function testHistoricTimestampIsAccepted(): void
    {
        $uuid = Uuid::generate(1000);

        $element = $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toString()]);

        $this->assertSame($uuid->toString(), $element->getId()?->toString());
    }

    public function testFutureTimestampIsRejected(): void
    {
        $uuid = Uuid::generate((int) floor(microtime(true) * 1000) + 3_600_000);

        $this->expectException(Client400BadContentException::class);
        $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toString()]);
    }

    public function testFutureTimestampWithinToleranceIsAccepted(): void
    {
        $uuid = Uuid::generate((int) floor(microtime(true) * 1000) + 10_000);

        $element = $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toString()]);

        $this->assertSame($uuid->toString(), $element->getId()?->toString());
    }

    public function testFutureTimestampIsAcceptedForBackups(): void
    {
        $uuid = Uuid::generate((int) floor(microtime(true) * 1000) + 3_600_000);

        $element = $this->buildService()->rawToElement(['type' => 'Tag', 'id' => $uuid->toString()], true);

        $this->assertSame($uuid->toString(), $element->getId()?->toString());
    }

    public function testLegacyUuidV4IsRejected(): void
    {
        $this->expectException(Client400BadContentException::class);
        $this->buildService()->rawToElement(['type' => 'Tag', 'id' => 'aee9e678-42fa-48cf-85e3-33638bebf0b1']);
    }
}
