<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\RequestIdService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[Small]
#[CoversClass(RequestIdService::class)]
class RequestIdServiceTest extends TestCase
{
    private function buildService(?Request $request): RequestIdService
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return new RequestIdService($requestStack);
    }

    public function testRequestIdIsTakenFromHeaderOfTheMainRequest(): void
    {
        $request = new Request();
        $request->headers->set('X-Request-Id', '3950e94a-2cd0-43ef-ac5a-8e116a4263c8');

        $this->assertSame('3950e94a-2cd0-43ef-ac5a-8e116a4263c8', $this->buildService($request)->getRequestId()->toString());
    }

    public function testRequestIdIsGeneratedWithoutHeader(): void
    {
        $requestId = $this->buildService(new Request())->getRequestId();

        $this->assertSame(4, $requestId->getFields()->getVersion());
    }

    public function testRequestIdIsGeneratedWithoutRequest(): void
    {
        $this->assertSame(4, $this->buildService(null)->getRequestId()->getFields()->getVersion());
    }

    public function testInvalidHeaderIsIgnored(): void
    {
        $request = new Request();
        $request->headers->set('X-Request-Id', 'not-a-uuid\n{"injected":true}');

        $requestId = $this->buildService($request)->getRequestId();

        $this->assertNotSame('not-a-uuid', $requestId->toString());
        $this->assertSame(4, $requestId->getFields()->getVersion());
    }

    public function testRequestIdStaysTheSameDuringTheRequest(): void
    {
        $service = $this->buildService(new Request());

        $this->assertSame($service->getRequestId(), $service->getRequestId());
    }

    public function testRequestIdIsReadFromTheRequestWhichIsAvailableWhenItIsFirstNeeded(): void
    {
        $requestStack = new RequestStack();
        $service = new RequestIdService($requestStack);
        $request = new Request();
        $request->headers->set('X-Request-Id', '3950e94a-2cd0-43ef-ac5a-8e116a4263c8');
        $requestStack->push($request);

        $this->assertSame('3950e94a-2cd0-43ef-ac5a-8e116a4263c8', $service->getRequestId()->toString());
    }
}
