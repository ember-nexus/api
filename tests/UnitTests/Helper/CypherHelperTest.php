<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Helper;

use App\Helper\CypherHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(CypherHelper::class)]
class CypherHelperTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function identifierProvider(): array
    {
        return [
            'plain' => ['Data', '`Data`'],
            'backtick is doubled' => ['A`B', '`A``B`'],
            'injection attempt stays inside' => ['X` DETACH DELETE n //', '`X`` DETACH DELETE n //`'],
            'empty' => ['', '``'],
        ];
    }

    #[DataProvider('identifierProvider')]
    public function testEscapeIdentifier(string $name, string $expected): void
    {
        $this->assertSame($expected, CypherHelper::escapeIdentifier($name));
    }
}
