<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\QueryExplanation\ExprLang;

use App\Service\QueryExplanation\ExprLang\ExprLangQueryExplanationService;
use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ExprLangQueryExplanationService::class)]
#[CoversClass(QueryExplanation::class)]
class ExprLangQueryExplanationServiceTest extends TestCase
{
    private function getService(): ExprLangQueryExplanationService
    {
        return new ExprLangQueryExplanationService(new QueryExplanationPayloadService());
    }

    public function testCompileErrorIsExplainedWithPosition(): void
    {
        $message = "compile error: unknown name a (1:5)\n | 1 + a\n | ....^";

        $explanation = $this->getService()->explain(json_encode(['error' => $message]));

        $this->assertSame('expr-lang', $explanation->source);
        $this->assertSame(QueryExplanation::CODE_INVALID_QUERY, $explanation->code);
        $this->assertTrue($explanation->isUserError);
        $this->assertTrue($explanation->isMessageSafe);
        $this->assertSame(
            ['kind' => 'compile error', 'message' => $message, 'line' => 1, 'column' => 5],
            $explanation->diagnostics
        );
    }

    public function testRuntimeErrorIsExplainedAsExecutionFailure(): void
    {
        $explanation = $this->getService()->explain(json_encode(['error' => 'runtime error: index out of range']));

        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertSame(['kind' => 'runtime error', 'message' => 'runtime error: index out of range'], $explanation->diagnostics);
        $this->assertTrue($explanation->isMessageSafe);
    }

    public function testOtherMessageHasNoKind(): void
    {
        $explanation = $this->getService()->explain(json_encode(['error' => 'something else']));

        $this->assertNull($explanation->diagnostics['kind']);
        $this->assertSame('something else', $explanation->diagnostics['message']);
    }

    public static function unusableOutputProvider(): array
    {
        return [
            'empty' => [''],
            'no json' => ['segfault'],
            'no error key' => ['{"foo": "bar"}'],
            'error is no string' => ['{"error": 5}'],
            'json scalar' => ['5'],
        ];
    }

    #[DataProvider('unusableOutputProvider')]
    public function testUnusableOutputResultsInEmptyExplanation(string $output): void
    {
        $explanation = $this->getService()->explain($output);

        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertTrue($explanation->isUserError);
        $this->assertFalse($explanation->isMessageSafe);
        $this->assertSame([], $explanation->diagnostics);
    }
}
