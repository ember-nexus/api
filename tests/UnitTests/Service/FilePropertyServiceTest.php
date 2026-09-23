<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\FilePropertyService;
use App\Service\FileService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;

#[Small]
#[CoversClass(FilePropertyService::class)]
class FilePropertyServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(
        ?Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory = null,
    ): FilePropertyService {
        return new FilePropertyService(
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
    }

    public function testReturnsNullWhenElementHasNoFileProperty(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(false);

        $result = $this->buildService()->parseFilePropertyFromElement($element->reveal());

        $this->assertNull($result);
    }

    public function testParsesExtensionFromFileProperty(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn(['extension' => 'png']);
        $element->getId()->willReturn(UuidV4::uuid4());

        $result = $this->buildService()->parseFilePropertyFromElement($element->reveal());

        $this->assertNotNull($result);
        $this->assertSame('png', $result->getExtension());
    }

    public function testDefaultsExtensionWhenKeyMissing(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn([]);
        $element->getId()->willReturn(UuidV4::uuid4());

        $result = $this->buildService()->parseFilePropertyFromElement($element->reveal());

        $this->assertNotNull($result);
        $this->assertSame(FileService::DEFAULT_EXTENSION, $result->getExtension());
    }

    public function testDefaultsExtensionWhenEmptyString(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn(['extension' => '']);
        $element->getId()->willReturn(UuidV4::uuid4());

        $result = $this->buildService()->parseFilePropertyFromElement($element->reveal());

        $this->assertNotNull($result);
        $this->assertSame(FileService::DEFAULT_EXTENSION, $result->getExtension());
    }

    public function testThrowsWhenFilePropertyIsNotAnArray(): void
    {
        $id = UuidV4::uuid4();
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn('not-an-array');
        $element->getId()->willReturn($id);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString('to be of type array, got string'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $this->buildService($server500LogicErrorExceptionFactory->reveal())->parseFilePropertyFromElement($element->reveal());
    }

    public function testThrowsWhenExtensionIsNotAString(): void
    {
        $id = UuidV4::uuid4();
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn(['extension' => 123]);
        $element->getId()->willReturn($id);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString("'file.extension'"))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $this->buildService($server500LogicErrorExceptionFactory->reveal())->parseFilePropertyFromElement($element->reveal());
    }

    public function testThrowsWithNullElementIdInMessageWhenIdIsNull(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('file')->willReturn(true);
        $element->getProperty('file')->willReturn('not-an-array');
        $element->getId()->willReturn(null);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();
        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString('element null'))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $this->expectException(Server500LogicErrorException::class);
        $this->buildService($server500LogicErrorExceptionFactory->reveal())->parseFilePropertyFromElement($element->reveal());
    }
}
