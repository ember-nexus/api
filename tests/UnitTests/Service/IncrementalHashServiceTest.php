<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Service\IncrementalHashService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(IncrementalHashService::class)]
class IncrementalHashServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(?Client409ConflictExceptionFactory $client409ConflictExceptionFactory = null): IncrementalHashService
    {
        return new IncrementalHashService(
            $client409ConflictExceptionFactory ?? $this->prophesize(Client409ConflictExceptionFactory::class)->reveal(),
        );
    }

    /**
     * @return resource
     */
    private function createResourceWithContent(string $content)
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }

    public function testFinalizeReturnsSha256HashOfContentFedInAsASingleChunk(): void
    {
        $content = 'some deterministic content used to verify the calculated hash';
        $resource = $this->createResourceWithContent($content);

        $service = $this->buildService();
        $context = $service->createContext('sha256');
        $service->updateFromResource($context, $resource);
        $hash = $service->finalize($context);

        $this->assertSame(hash('sha256', $content), $hash);
    }

    public function testUpdateFromResourceRewindsResourceAfterHashing(): void
    {
        $content = 'content that must still be fully readable afterwards';
        $resource = $this->createResourceWithContent($content);

        $service = $this->buildService();
        $context = $service->createContext('sha256');
        $service->updateFromResource($context, $resource);

        $this->assertSame(0, \Safe\ftell($resource));
        $this->assertSame($content, \Safe\stream_get_contents($resource));
    }

    public function testHashCanBeAccumulatedAcrossMultipleChunksMatchingASingleShotHash(): void
    {
        $chunks = ['first chunk of data, ', 'second chunk of data, ', 'and the final chunk.'];

        $service = $this->buildService();
        $context = $service->createContext('sha256');
        foreach ($chunks as $chunk) {
            $resource = $this->createResourceWithContent($chunk);
            $service->updateFromResource($context, $resource);
        }
        $hash = $service->finalize($context);

        $this->assertSame(hash('sha256', implode('', $chunks)), $hash);
    }

    public function testSerializedContextCanBeResumedAndProducesTheSameHashAsAnUninterruptedRun(): void
    {
        $firstChunk = 'first chunk of a resumed upload, ';
        $secondChunk = 'and its second, final chunk.';

        $service = $this->buildService();

        $context = $service->createContext('sha256');
        $service->updateFromResource($context, $this->createResourceWithContent($firstChunk));
        $serialized = $service->serializeContextForStorage($context);

        $resumedContext = $service->unserializeContextFromStorage($serialized);
        $service->updateFromResource($resumedContext, $this->createResourceWithContent($secondChunk));
        $hash = $service->finalize($resumedContext);

        $this->assertSame(hash('sha256', $firstChunk.$secondChunk), $hash);
    }

    public function testUnserializeContextFromStorageThrowsConflictExceptionOnGarbageInput(): void
    {
        $exception = $this->prophesize(Client409ConflictException::class)->reveal();

        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::type('string'))->shouldBeCalledOnce()->willReturn($exception);

        $service = $this->buildService($client409ConflictExceptionFactory->reveal());

        $this->expectException(Client409ConflictException::class);

        $service->unserializeContextFromStorage('this is not valid base64-encoded serialized data!!!');
    }

    public function testUnserializeContextFromStorageThrowsConflictExceptionWhenDecodedValueIsNotAHashContext(): void
    {
        $exception = $this->prophesize(Client409ConflictException::class)->reveal();

        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::type('string'))->shouldBeCalledOnce()->willReturn($exception);

        $service = $this->buildService($client409ConflictExceptionFactory->reveal());

        $this->expectException(Client409ConflictException::class);

        $service->unserializeContextFromStorage(base64_encode(serialize('just a string, not a HashContext')));
    }
}
