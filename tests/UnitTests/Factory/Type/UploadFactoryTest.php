<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\UploadInterface;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Service\PropertyParseService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(UploadFactory::class)]
class UploadFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function buildUploadFactory(
        ?PropertyParseService $propertyParseService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): UploadFactory {
        return new UploadFactory(
            $propertyParseService ?? $this->prophesize(PropertyParseService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
        );
    }

    public function testCreateUploadFromElement(): void
    {
        $elementId = Uuid::fromString('95ddb14a-bf07-4bab-958a-64ec5240800b');
        $uploadTarget = Uuid::fromString('5c9d0f01-251e-47a0-983c-b39201b07a32');
        $uploadOwner = Uuid::fromString('5bdafd97-fe7c-4e68-b094-63da5c992a69');
        $expires = new DateTime();

        $properties = [];

        $element = $this->prophesize(NodeElementInterface::class);
        $element->getLabel()->shouldBeCalledOnce()->willReturn('Upload');
        $element->getId()->shouldBeCalledOnce()->willReturn($elementId);
        $element->getProperties()->shouldBeCalledOnce()->willReturn($properties);

        $propertyParseService = $this->prophesize(PropertyParseService::class);
        $propertyParseService->getUploadLengthFromProperties(Argument::is($properties))->willReturn(1111);
        $propertyParseService->getUploadOffsetFromProperties(Argument::is($properties))->willReturn(2222);
        $propertyParseService->getIsUploadCompleteFromProperties(Argument::is($properties))->willReturn(false);
        $propertyParseService->getUploadTargetFromProperties(Argument::is($properties))->willReturn($uploadTarget);
        $propertyParseService->getAlreadyUploadedChunksFromProperties(Argument::is($properties))->willReturn(5);
        $propertyParseService->getUploadOwnerFromProperties(Argument::is($properties))->willReturn($uploadOwner);
        $propertyParseService->getExtensionFromProperties(Argument::is($properties))->willReturn('some-ext');
        $propertyParseService->getExpiresFromProperties(Argument::is($properties))->willReturn($expires);
        $propertyParseService->getHashStateFromProperties(Argument::is($properties))->willReturn('some-hash-state');

        $uploadFactory = $this->buildUploadFactory(
            propertyParseService: $propertyParseService->reveal()
        );

        $upload = $uploadFactory->createUploadFromElement($element->reveal());

        $this->assertSame($elementId, $upload->getId());
        $this->assertSame(1111, $upload->getUploadLength());
        $this->assertSame(2222, $upload->getUploadOffset());
        $this->assertSame(false, $upload->isUploadComplete());
        $this->assertSame($uploadTarget, $upload->getUploadTarget());
        $this->assertSame(5, $upload->getAlreadyUploadedChunks());
        $this->assertSame($uploadOwner, $upload->getUploadOwner());
        $this->assertSame('some-ext', $upload->getExtension());
        $this->assertSame($expires, $upload->getExpires());
        $this->assertSame('some-hash-state', $upload->getHashState());
    }

    public function testCreateUploadFromElementThrowsWhenElementIsNotANode(): void
    {
        $element = $this->prophesize(RelationElementInterface::class);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is('Upload element must be a node, not a relation.'))->shouldBeCalledOnce()->willReturn($exception);

        $uploadFactory = $this->buildUploadFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $uploadFactory->createUploadFromElement($element->reveal());
    }

    public function testCreateUploadFromElementThrowsWhenNodeIsNotOfTypeUpload(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->getLabel()->shouldBeCalledOnce()->willReturn('NotAnUpload');

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is('Can not cast element of type NotAnUpload to upload.'))->shouldBeCalledOnce()->willReturn($exception);

        $uploadFactory = $this->buildUploadFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $uploadFactory->createUploadFromElement($element->reveal());
    }

    public function testCreateUploadFromElementThrowsWhenNodeHasMissingId(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->getLabel()->shouldBeCalledOnce()->willReturn('Upload');
        $element->getId()->shouldBeCalledOnce()->willReturn(null);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is('Upload expects element id to not be null.'))->shouldBeCalledOnce()->willReturn($exception);

        $uploadFactory = $this->buildUploadFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $uploadFactory->createUploadFromElement($element->reveal());
    }

    public function testMarkUploadAsComplete(): void
    {
        $uploadId = Uuid::fromString('c4c01f55-3b29-48e9-aa4a-814ea1b7f3c8');
        $uploadTarget = Uuid::fromString('9c6dd5e1-b58c-4b04-be08-c81cd0eda6e4');
        $uploadOwner = Uuid::fromString('db37197b-415d-4659-825e-f5d374b4d2a7');
        $expires = new DateTime();

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadLength()->shouldBeCalledOnce()->willReturn(1111);
        $upload->getUploadOffset()->shouldBeCalledOnce()->willReturn(2222);
        $upload->isUploadComplete()->shouldNotBeCalled();
        $upload->getUploadTarget()->shouldBeCalledOnce()->willReturn($uploadTarget);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(3);
        $upload->getUploadOwner()->shouldBeCalledOnce()->willReturn($uploadOwner);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');
        $upload->getExpires()->shouldBeCalledOnce()->willReturn($expires);
        $upload->getHashState()->shouldBeCalledOnce()->willReturn('some-hash-state');

        $uploadFactory = $this->buildUploadFactory();

        $result = $uploadFactory->markUploadAsComplete($upload->reveal());

        $this->assertSame($uploadId, $result->getId());
        $this->assertSame(1111, $result->getUploadLength());
        $this->assertSame(2222, $result->getUploadOffset());
        $this->assertSame(true, $result->isUploadComplete());
        $this->assertSame($uploadTarget, $result->getUploadTarget());
        $this->assertSame(3, $result->getAlreadyUploadedChunks());
        $this->assertSame($uploadOwner, $result->getUploadOwner());
        $this->assertSame('ext', $result->getExtension());
        $this->assertSame($expires, $result->getExpires());
        $this->assertSame('some-hash-state', $result->getHashState());
    }

    public function testAddNewChunkToUpload(): void
    {
        $uploadId = Uuid::fromString('7f27bcda-3d08-4790-b6f0-a5727fbc2bde');
        $uploadTarget = Uuid::fromString('f253379f-3533-4719-8337-871df7d336f6');
        $uploadOwner = Uuid::fromString('2be74c20-89d7-4f61-9c5b-c47a7ae11b5e');
        $expires = new DateTime();

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadLength()->shouldBeCalledOnce()->willReturn(20000);
        $upload->getUploadOffset()->shouldBeCalledOnce()->willReturn(10000);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);
        $upload->getUploadTarget()->shouldBeCalledOnce()->willReturn($uploadTarget);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(3);
        $upload->getUploadOwner()->shouldBeCalledOnce()->willReturn($uploadOwner);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');
        $upload->getExpires()->shouldBeCalledOnce()->willReturn($expires);
        $upload->getHashState()->shouldBeCalledOnce()->willReturn('some-hash-state');

        $uploadFactory = $this->buildUploadFactory();

        $result = $uploadFactory->addNewChunkToUpload($upload->reveal(), 9999);

        $this->assertSame($uploadId, $result->getId());
        $this->assertSame(20000, $result->getUploadLength());
        $this->assertSame(19999, $result->getUploadOffset());
        $this->assertSame(false, $result->isUploadComplete());
        $this->assertSame($uploadTarget, $result->getUploadTarget());
        $this->assertSame(4, $result->getAlreadyUploadedChunks());
        $this->assertSame($uploadOwner, $result->getUploadOwner());
        $this->assertSame('ext', $result->getExtension());
        $this->assertSame($expires, $result->getExpires());
        $this->assertSame('some-hash-state', $result->getHashState());
    }

    public function testAddNewChunkToUploadUsesProvidedHashStateInsteadOfExistingUploadHashState(): void
    {
        $uploadId = Uuid::fromString('7f27bcda-3d08-4790-b6f0-a5727fbc2bde');
        $uploadTarget = Uuid::fromString('f253379f-3533-4719-8337-871df7d336f6');
        $uploadOwner = Uuid::fromString('2be74c20-89d7-4f61-9c5b-c47a7ae11b5e');
        $expires = new DateTime();

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadLength()->shouldBeCalledOnce()->willReturn(20000);
        $upload->getUploadOffset()->shouldBeCalledOnce()->willReturn(10000);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);
        $upload->getUploadTarget()->shouldBeCalledOnce()->willReturn($uploadTarget);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(3);
        $upload->getUploadOwner()->shouldBeCalledOnce()->willReturn($uploadOwner);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');
        $upload->getExpires()->shouldBeCalledOnce()->willReturn($expires);
        $upload->getHashState()->shouldNotBeCalled();

        $uploadFactory = $this->buildUploadFactory();

        $result = $uploadFactory->addNewChunkToUpload($upload->reveal(), 9999, 'new-hash-state');

        $this->assertSame('new-hash-state', $result->getHashState());
    }
}
