<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Helper;

use App\Helper\Regex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(Regex::class)]
class RegexTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function types(): iterable
    {
        yield 'simple' => ['Data', true];
        yield 'relation' => ['HAS_READ_ACCESS', true];
        yield 'digits' => ['Type2', true];
        yield 'longest' => [str_repeat('a', 128), true];
        yield 'too long' => [str_repeat('a', 129), false];
        yield 'empty' => ['', false];
        yield 'leading digit' => ['1Type', false];
        yield 'leading underscore' => ['_Type', false];
        yield 'semicolon' => ['a;b', false];
        yield 'space' => ['a b', false];
        yield 'backtick' => ['a`b', false];
        yield 'trailing newline' => ["Data\n", false];
        yield 'unicode' => ['Dätä', false];
        yield 'dash' => ['a-b', false];
    }

    #[DataProvider('types')]
    public function testType(string $type, bool $valid): void
    {
        $this->assertSame($valid, 1 === preg_match(Regex::TYPE, $type));
    }
}
