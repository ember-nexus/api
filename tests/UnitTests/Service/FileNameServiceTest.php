<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\FileNameService;
use App\Service\StringService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(FileNameService::class)]
#[AllowMockObjectsWithoutExpectations]
class FileNameServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildFileNameService(): FileNameService
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $server500Bag = $this->createMock(ParameterBagInterface::class);
        $server500Bag->method('get')->willReturn('dev');
        $server500LogicExceptionFactory = new Server500LogicErrorExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $this->createMock(LoggerInterface::class)
        );
        $stringService = new StringService($server500LogicExceptionFactory);

        return new FileNameService($stringService);
    }

    public static function getAsciiSafeFileNameProvider(): array
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

    #[DataProvider('getAsciiSafeFileNameProvider')]
    public function testGetAsciiSafeFileName(string $input, string $output): void
    {
        $fileUtilService = $this->buildFileNameService();
        $this->assertSame($output, $fileUtilService->getAsciiSafeFileName($input));
    }

    public static function removeReservedCharactersFromFileNameProvider(): array
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

    #[DataProvider('removeReservedCharactersFromFileNameProvider')]
    public function testRemoveReservedCharactersFromFileName(string $input, string $output): void
    {
        $fileUtilService = $this->buildFileNameService();
        $this->assertSame($output, $fileUtilService->removeReservedCharactersFromFileName($input));
    }

    public static function fileNameFromPartsProvider(): array
    {
        return [
            ['name', 'ext', 'name.ext'],
            ['name', str_repeat('e', 100), 'name.'.str_repeat('e', 64)],
            ['name', 'ext    with          long      extension      and      whitespace ', 'name.extwithlongextensionandwhitespace'],
            ['name-----1---------2---------3---------4---------5---------6---------7---------8---------9---------a---------b---------c---------d---------e---------f---------g---------h---------i---------j---------k---------l---------m---------n---------o---------p---------q---------r---------s---------t---------u---------v', 'ext', 'name-----1---------2---------3---------4---------5---------6---------7---------8---------9---------a---------b---------c---------d---------e---------f---------g---------h---------i---------j---------k---------l---------m---------n---------o---------p-.ext'],
            ['', 'env', '.env'],
            ['Makefile', '', 'Makefile'],
            ['  Makefile ', '', 'Makefile'],
            [str_repeat('n', 300), '', str_repeat('n', 255)],
        ];
    }

    #[DataProvider('fileNameFromPartsProvider')]
    public function testBuildFileNameFromParts(string $name, string $extension, string $result): void
    {
        $fileUtilService = $this->buildFileNameService();
        $this->assertSame($result, $fileUtilService->buildFileNameFromParts($name, $extension));
    }
}
