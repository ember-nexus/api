<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementService;
use App\Service\FilePropertyService;
use App\Service\FileService;
use App\Service\StringService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ElementService::class)]
class ElementServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildService(): ElementService
    {
        $errorFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $errorFactory
            ->createFromTemplate(Argument::cetera())
            ->willReturn(new Server500LogicErrorException('logic-error'));
        $errorFactory = $errorFactory->reveal();

        return new ElementService(
            new FileService(
                $this->prophesize(EmberNexusConfiguration::class)->reveal(),
                $this->prophesize(StringService::class)->reveal(),
                $errorFactory,
            ),
            new FilePropertyService($errorFactory),
            $errorFactory,
        );
    }

    private function buildNode(?string $id = '9a1a5c5e-3f5c-4a0f-9d0e-2a3f3a3b8a11'): NodeElement
    {
        $node = new NodeElement();
        if (null !== $id) {
            $node->setId(Uuid::fromString($id));
        }

        return $node;
    }

    public function testGetElementIdReturnsId(): void
    {
        $node = $this->buildNode();
        $this->assertSame($node->getId(), $this->buildService()->getElementId($node));
    }

    public function testGetElementIdThrowsWithoutId(): void
    {
        $this->expectException(Server500LogicErrorException::class);
        $this->buildService()->getElementId($this->buildNode(null));
    }

    public function testHasFileIsFalseWithoutFlag(): void
    {
        $this->assertFalse($this->buildService()->hasFile($this->buildNode()));
    }

    public function testHasFileIsTrueOnlyForTrueFlag(): void
    {
        $node = $this->buildNode();
        $node->addProperty('hasFile', true);
        $this->assertTrue($this->buildService()->hasFile($node));

        $node->addProperty('hasFile', false);
        $this->assertFalse($this->buildService()->hasFile($node));

        $node->addProperty('hasFile', 'true');
        $this->assertFalse($this->buildService()->hasFile($node));
    }

    public function testHasFileWorksForRelations(): void
    {
        $relation = new RelationElement();
        $relation->addProperty('hasFile', true);
        $this->assertTrue($this->buildService()->hasFile($relation));
    }

    public function testFileNameFallsBackToIdAndDefaultExtension(): void
    {
        $this->assertSame(
            sprintf('9a1a5c5e-3f5c-4a0f-9d0e-2a3f3a3b8a11.%s', FileService::DEFAULT_EXTENSION),
            $this->buildService()->getFileName($this->buildNode())
        );
    }

    public function testFileNameUsesNameAndFileExtension(): void
    {
        $node = $this->buildNode();
        $node->addProperty('name', 'My report');
        $node->addProperty('file', ['extension' => 'pdf']);

        $this->assertSame('My report.pdf', $this->buildService()->getFileName($node));
    }

    public function testFileNameRemovesReservedCharacters(): void
    {
        $node = $this->buildNode();
        $node->addProperty('name', 'a/b:c*"d');

        $this->assertSame(
            sprintf('abcd.%s', FileService::DEFAULT_EXTENSION),
            $this->buildService()->getFileName($node)
        );
    }

    public function testFileNameFallsBackToIdIfNameBecomesEmptyOrIsNotAString(): void
    {
        $node = $this->buildNode();
        $expected = sprintf('9a1a5c5e-3f5c-4a0f-9d0e-2a3f3a3b8a11.%s', FileService::DEFAULT_EXTENSION);

        $node->addProperty('name', '/:*');
        $this->assertSame($expected, $this->buildService()->getFileName($node));

        $node->addProperty('name', 123);
        $this->assertSame($expected, $this->buildService()->getFileName($node));
    }

    public function testGetFileNameExtensionDefaultsWithoutFileProperty(): void
    {
        $this->assertSame(FileService::DEFAULT_EXTENSION, $this->buildService()->getFileNameExtension($this->buildNode()));
    }

    public function testGetFileNameExtensionReadsFileProperty(): void
    {
        $node = $this->buildNode();
        $node->addProperty('file', ['extension' => 'txt']);
        $this->assertSame('txt', $this->buildService()->getFileNameExtension($node));
    }
}
