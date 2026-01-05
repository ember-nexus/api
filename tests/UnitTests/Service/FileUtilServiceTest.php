<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\ElementService;
use App\Service\FileUtilService;
use App\Type\NodeElement;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(FileUtilService::class)]
class FileUtilServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildFileUtilService(
        ?ElementService $elementService = null,
        ?Server500LogicExceptionFactory $server500LogicExceptionFactory = null,
    ): FileUtilService {
        if (null === $server500LogicExceptionFactory) {
            $server500LogicExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);
            $server500LogicExceptionFactory = $server500LogicExceptionFactory->reveal();
        }
        if (null === $elementService) {
            $elementService = new ElementService($server500LogicExceptionFactory);
        }

        return new FileUtilService(
            $elementService,
            $server500LogicExceptionFactory
        );
    }

    public static function sanitizedStringProvider(): array
    {
        return [
            ['', ''],
            ['    ', ''],
            ['hello', 'hello'],
            ['Hello', 'Hello'],
            ['HelLo', 'HelLo'],
            ['  prefix trim', 'prefix trim'],
            ['suffix trim  ', 'suffix trim'],
            ['multi word name', 'multi word name'],
            ['"quoted string"', 'quoted string'],
            ['wild card *', 'wild card'],
            [' * prefix sanitized trim', 'prefix sanitized trim'],
            ['suffix sanitized trim * ', 'suffix sanitized trim'],
            ['slash /', 'slash'],
            ['colon :', 'colon'],
            ['less <', 'less'],
            ['greater >', 'greater'],
            ['question ?', 'question'],
            ['reverse slash \\', 'reverse slash'],
            ['pipe |', 'pipe'],
        ];
    }

    #[DataProvider('sanitizedStringProvider')]
    public function testSanitizeString(string $input, string $output): void
    {
        $fileUtilService = $this->buildFileUtilService();
        $this->assertSame($output, $fileUtilService->sanitizeString($input));
    }

    public function testGetBaseNameContentFallsBackToElementId(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('6913e58f-bb25-4ec0-b9b4-3dc86869c643');
        $element = new NodeElement();
        $element->setId($elementId);

        $this->assertSame('6913e58f-bb25-4ec0-b9b4-3dc86869c643', $fileUtilService->getBaseNameContent($element));
    }

    public function testGetBaseNameContentFallsBackToElementIdIfNameIsNotString(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('6913e58f-bb25-4ec0-b9b4-3dc86869c643');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', 1234);

        $this->assertSame('6913e58f-bb25-4ec0-b9b4-3dc86869c643', $fileUtilService->getBaseNameContent($element));
    }

    public function testGetBaseNameContentFallsBackToElementIdIfNameIsEmpty(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('6913e58f-bb25-4ec0-b9b4-3dc86869c643');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', '');

        $this->assertSame('6913e58f-bb25-4ec0-b9b4-3dc86869c643', $fileUtilService->getBaseNameContent($element));
    }

    public function testGetBaseNameContentFallsBackToElementIdIfNameIsEmptyWhenTrimmed(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('6913e58f-bb25-4ec0-b9b4-3dc86869c643');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', '   ');

        $this->assertSame('6913e58f-bb25-4ec0-b9b4-3dc86869c643', $fileUtilService->getBaseNameContent($element));
    }

    public function testGetBaseNameContentReturnsSanitizedNameWhenPossible(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('6913e58f-bb25-4ec0-b9b4-3dc86869c643');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', 'some name   ');

        $this->assertSame('some name', $fileUtilService->getBaseNameContent($element));
    }

    public function testGetExtensionFallsBackToDefaultExtension(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);

        $this->assertSame('bin', $fileUtilService->getExtension($element));
    }

    public function testGetExtensionThrowsWhenFilePropertyIsNotArray(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('url');
        $urlGenerator = $urlGenerator->reveal();

        $server500Bag = $this->prophesize(ParameterBagInterface::class);
        $server500Bag->get(Argument::cetera())->shouldBeCalledOnce()->willReturn('dev');
        $server500Bag = $server500Bag->reveal();

        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $server500LogicExceptionFactory = new Server500LogicExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $logger
        );

        $fileUtilService = $this->buildFileUtilService(server500LogicExceptionFactory: $server500LogicExceptionFactory);

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('file', 1234);

        try {
            $fileUtilService->getExtension($element);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame("Expected property 'file' of element 976e593a-95dc-489f-8257-986830f35526 to be of type array, got int.", $e->getDetail());
        }
    }

    public function testGetExtensionFallsBackToDefaultExtensionIfFileExtensionIsMissing(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('file', []);

        $this->assertSame('bin', $fileUtilService->getExtension($element));
    }

    public function testGetExtensionReturnsFileExtension(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('file', [
            'extension' => 'abc',
        ]);

        $this->assertSame('abc', $fileUtilService->getExtension($element));
    }

    public function testGetExtensionReturnsSanitizedFileExtension(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('file', [
            'extension' => 'abc*?123 ',
        ]);

        $this->assertSame('abc123', $fileUtilService->getExtension($element));
    }

    public function testGetFileNameFallsBackToDefaultFileName(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);

        $this->assertSame('976e593a-95dc-489f-8257-986830f35526.bin', $fileUtilService->getFileName($element));
    }

    public function testGetFileNameReturnsSanitizedFileNameIfPossible(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', 'custom');
        $element->addProperty('file', [
            'extension' => 'txt',
        ]);

        $this->assertSame('custom.txt', $fileUtilService->getFileName($element));
    }

    public function testGetFileNameLimitsFileNameLength(): void
    {
        $fileUtilService = $this->buildFileUtilService();

        $elementId = Uuid::fromString('976e593a-95dc-489f-8257-986830f35526');
        $element = new NodeElement();
        $element->setId($elementId);
        $element->addProperty('name', str_repeat('ooooooooo-', 30)); // name is 300 chars long
        $element->addProperty('file', [
            'extension' => str_repeat('ooooooooo-', 10), // extension is 100 chars long
        ]);

        $this->assertSame(
            'ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-oo.ooooooooo-ooooooooo-ooooooooo-oo',
            $fileUtilService->getFileName($element)
        );
    }

    public static function asciiSafeFileNameProvider(): array
    {
        return [
            ['', ''],
            ['abc', 'abc'],
            [str_repeat('ooooooooo-', 30), 'ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooooooo-ooooo'],
            ['abc.txt', 'abc.txt'],
            ['AbC.txt', 'AbC.txt'],
            ['aä-oö-uü.txt', 'aa-oo-uu.txt'],
            ['0123.txt', '0123.txt'],
            ['hello world.txt', 'hello world.txt'],
            ['fichier été résumé.txt', 'fichier ete resume.txt'],
            ['garçon.txt', 'garcon.txt'],
            ['mañana.txt', 'manana.txt'],
            ['📄 emoji.txt', 'page facing up emoji.txt'],
            ['😃 emoji.txt', 'grinning face with big eyes emoji.txt'],
            ['✅ emoji.txt', 'check mark button emoji.txt'],
            ['😃😃😃😃😃😃😃😃😃😃😃😃😃😃😃😃😃😃😃.txt', 'grinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning face with big eyesgrinning.txt'],
            ['서울.txt', 'seoul.txt'],
            ['한국어.txt', 'hangug-eo.txt'],
            ['हिन्दी.txt', 'hindi.txt'],
            ['தமிழ்.txt', 'tamil.txt'],
            ['বাংলা.txt', 'banla.txt'],
            ['日本語.txt', 'ri ben yu.txt'],
        ];
    }

    #[DataProvider('asciiSafeFileNameProvider')]
    public function testGetAsciiSafeFileName(string $input, string $output): void
    {
        $fileUtilService = $this->buildFileUtilService();
        $this->assertSame($output, $fileUtilService->getAsciiSafeFileName($input));
    }
}
