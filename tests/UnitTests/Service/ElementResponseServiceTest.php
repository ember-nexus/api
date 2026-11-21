<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementResponseService;
use App\Service\ElementToRawService;
use App\Type\NodeElement;
use App\Type\Response\ElementResponse;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(ElementResponseService::class)]
class ElementResponseServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testBuildElementResponseFromIdWithElement(): void
    {
        $elementId = Uuid::fromString('02IiNr0G8viOVKkEXFa7KEiSnEQE0VXKGLIWH');
        $element = new NodeElement();
        $data = ['hello' => 'world'];

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager
            ->getElement(Argument::is($elementId))
            ->shouldBeCalledOnce()
            ->willReturn($element);

        $elementToRawService = $this->prophesize(ElementToRawService::class);
        $elementToRawService
            ->elementToRaw(Argument::is($element))
            ->shouldBeCalledOnce()
            ->willReturn($data);

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);

        $elementResponseService = new ElementResponseService(
            $elementManager->reveal(),
            $elementToRawService->reveal(),
            $server500LogicExceptionFactory->reveal()
        );

        $response = $elementResponseService->buildElementResponseFromId($elementId);
        $this->assertInstanceOf(ElementResponse::class, $response);
        $this->assertSame('{"hello":"world"}', $response->getContent());
    }

    public function testBuildElementResponseFromIdWithoutElement(): void
    {
        $elementId = Uuid::fromString('02IiNr0G8viOVKjkDryxoJys4N19LAEYL8Yim');
        $exception = new Server500LogicErrorException('blub');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager
            ->getElement(Argument::is($elementId))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $elementToRawService = $this->prophesize(ElementToRawService::class);

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicExceptionFactory
            ->createFromTemplate(Argument::is("Unable to find element with the id '02IiNr0G8viOVKjkDryxoJys4N19LAEYL8Yim'."))
            ->shouldBeCalledOnce()
            ->willReturn($exception);

        $elementResponseService = new ElementResponseService(
            $elementManager->reveal(),
            $elementToRawService->reveal(),
            $server500LogicExceptionFactory->reveal()
        );

        $this->expectExceptionObject($exception);

        $elementResponseService->buildElementResponseFromId($elementId);
    }
}
