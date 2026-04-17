<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\StringService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(StringService::class)]
class StringServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildStringService(
        ?Server500LogicErrorExceptionFactory $server500LogicExceptionFactory = null,
    ): StringService {
        if (null === $server500LogicExceptionFactory) {
            $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
            $server500LogicExceptionFactory = $server500LogicExceptionFactory->reveal();
        }

        return new StringService(
            $server500LogicExceptionFactory
        );
    }

    public static function getAsciiSafeStringProvider(): array
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
            ['', ''],
            ['abc', 'abc'],
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
            ['서울.txt', 'seoul.txt'],
            ['한국어.txt', 'hangug-eo.txt'],
            ['हिन्दी.txt', 'hindi.txt'],
            ['தமிழ்.txt', 'tamil.txt'],
            ['বাংলা.txt', 'banla.txt'],
            ['日本語.txt', 'ri ben yu.txt'],
        ];
    }

    #[DataProvider('getAsciiSafeStringProvider')]
    public function testGetAsciiSafeString(string $input, string $output): void
    {
        $fileService = $this->buildStringService();
        $this->assertSame($output, $fileService->getAsciiSafeString($input));
    }
}
