<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client408RequestTimeoutException;
use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use App\Service\RequestContentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(RequestContentService::class)]
class RequestContentServiceTest extends TestCase
{
    private function buildService(): RequestContentService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('http://example.com/error/408/request-timeout');

        return new RequestContentService(new Client408RequestTimeoutExceptionFactory($urlGenerator));
    }

    public function testContentWithoutContentLengthIsReturned(): void
    {
        $request = new Request(content: '{"a":1}');

        $this->assertSame('{"a":1}', $this->buildService()->getContent($request));
    }

    public function testContentMatchingContentLengthIsReturned(): void
    {
        $request = new Request(server: ['CONTENT_LENGTH' => '7'], content: '{"a":1}');

        $this->assertSame('{"a":1}', $this->buildService()->getContent($request));
    }

    public function testContentShorterThanContentLengthIsAnsweredWithRequestTimeout(): void
    {
        $request = new Request(server: ['CONTENT_LENGTH' => '20'], content: '{"a":1}');

        try {
            $this->buildService()->getContent($request);
            $this->fail('Expected request timeout.');
        } catch (Client408RequestTimeoutException $exception) {
            $this->assertSame(408, $exception->getStatus());
            $this->assertStringContainsString('received 7 of 20 bytes', $exception->getDetail());
        }
    }

    public function testInvalidContentLengthIsIgnored(): void
    {
        $request = new Request(server: ['CONTENT_LENGTH' => 'abc'], content: '{"a":1}');

        $this->assertSame('{"a":1}', $this->buildService()->getContent($request));
    }
}
