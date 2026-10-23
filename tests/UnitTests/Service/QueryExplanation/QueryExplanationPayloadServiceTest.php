<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\QueryExplanation;

use App\Service\QueryExplanation\QueryExplanationPayloadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Small]
#[CoversClass(QueryExplanationPayloadService::class)]
class QueryExplanationPayloadServiceTest extends TestCase
{
    public function testAllowlistKeepsOnlyAllowlistedScalarProperties(): void
    {
        $service = new QueryExplanationPayloadService();

        $result = $service->allowlist(
            ['a' => 1, 'b' => 'x', 'c' => null, 'secret' => 'data', 'd' => ['nested' => 1], 'e' => new stdClass()],
            ['a', 'b', 'c', 'd', 'e', 'missing']
        );

        $this->assertSame(['a' => 1, 'b' => 'x', 'c' => null], $result);
    }

    public function testAllowlistKeepsListsOfScalarsButNotMixedLists(): void
    {
        $service = new QueryExplanationPayloadService();

        $result = $service->allowlist(
            ['codes' => ['a', 'b', null, 3], 'mixed' => ['a', ['b']], 'assoc' => ['k' => 'v']],
            ['codes', 'mixed', 'assoc']
        );

        $this->assertSame(['codes' => ['a', 'b', null, 3]], $result);
    }

    public function testAllowlistAppliesNestedAllowlistToMapsAndLists(): void
    {
        $service = new QueryExplanationPayloadService();

        $result = $service->allowlist(
            [
                'position' => ['line' => 1, 'secret' => 'x'],
                'items' => [['code' => 'a', 'secret' => 'x'], 'not-an-array', ['code' => 'b']],
                'scalar' => 'value',
                'secret' => 'x',
            ],
            [
                'position' => ['line'],
                'items' => ['code'],
                'scalar' => ['code'],
                'missing' => ['code'],
            ]
        );

        $this->assertSame(
            [
                'position' => ['line' => 1],
                'items' => [['code' => 'a'], ['code' => 'b']],
            ],
            $result
        );
    }

    public function testAllowlistIgnoresNonStringNames(): void
    {
        $service = new QueryExplanationPayloadService();

        $this->assertSame([], $service->allowlist(['a' => 1], [5]));
    }

    public function testTruncateKeepsSmallData(): void
    {
        $service = new QueryExplanationPayloadService();

        $this->assertSame(['a' => 'b'], $service->truncate(['a' => 'b']));
    }

    public function testTruncateReplacesTooBigData(): void
    {
        $service = new QueryExplanationPayloadService();

        $result = $service->truncate(['a' => str_repeat('ä', 100)], 50);

        $this->assertTrue($result['truncated']);
        $this->assertSame(strlen('{"a":"'.str_repeat('ä', 100).'"}'), $result['originalSizeInBytes']);
        $this->assertLessThanOrEqual(50, strlen($result['excerpt']));
        $this->assertTrue(mb_check_encoding($result['excerpt'], 'UTF-8'));
        $this->assertStringStartsWith('{"a":"ä', $result['excerpt']);
    }

    public function testTruncateUsesDefaultLimit(): void
    {
        $service = new QueryExplanationPayloadService();

        $result = $service->truncate(['a' => str_repeat('a', QueryExplanationPayloadService::MAX_DIAGNOSTICS_BYTES)]);

        $this->assertTrue($result['truncated']);
    }

    public function testSanitizeAppliesAllowlistAndTruncation(): void
    {
        $service = new QueryExplanationPayloadService();

        $this->assertSame(['a' => 1], $service->sanitize(['a' => 1, 'secret' => 2], ['a']));
        $result = $service->sanitize(['a' => str_repeat('a', 20000), 'secret' => 2], ['a']);
        $this->assertTrue($result['truncated']);
        $this->assertStringNotContainsString('secret', $result['excerpt']);
    }
}
