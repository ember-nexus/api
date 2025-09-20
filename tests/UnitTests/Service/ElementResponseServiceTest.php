<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\ElementResponse;
use App\Service\ElementManager;
use App\Service\ElementResponseService;
use App\Service\ElementToRawService;
use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ElementResponseService::class)]
class ElementResponseServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testBuildElementResponseFromIdWithElement(): void
    {
        $elementId = Uuid::fromString('71a8f62c-0fae-42ee-9c38-e8bdc9228f1e');
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

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);

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
        $elementId = Uuid::fromString('336eff5e-ff4e-463f-abed-168c06af5fae');
        $exception = new Server500LogicErrorException('blub');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager
            ->getElement(Argument::is($elementId))
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $elementToRawService = $this->prophesize(ElementToRawService::class);

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);
        $server500LogicExceptionFactory
            ->createFromTemplate(Argument::is("Unable to find element with the id '336eff5e-ff4e-463f-abed-168c06af5fae'."))
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
