<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client403ForbiddenException;
use App\Exception\Client422UnprocessableContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Client422UnprocessableContentExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ExpressionService;
use App\Tests\UnitTests\AssertLoggerTrait;
use Beste\Psr\Log\TestLogger;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

#[Small]
#[CoversClass(ExpressionService::class)]
class ExpressionServiceTest extends TestCase
{
    use AssertLoggerTrait;
    use ProphecyTrait;

    private function getExpressionService(
        ?AuthProvider $authProvider = null,
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?LoggerInterface $logger = null,
        bool $simulateTimeout = false,
    ): ExpressionService {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $client403ForbiddenExceptionFactory = new Client403ForbiddenExceptionFactory($urlGenerator);
        $client422UnprocessableContentExceptionFactory = new Client422UnprocessableContentExceptionFactory($urlGenerator);

        if (null === $authProvider) {
            $authProvider = $this->prophesize(AuthProvider::class);
            $authProvider->getUserId()->willReturn(Uuid::fromString('3f34b034-bbe4-4461-a023-dc2920c1b1c3'));
            $authProvider = $authProvider->reveal();
        }

        if (null === $emberNexusConfiguration) {
            $emberNexusConfiguration = new EmberNexusConfiguration();
            $emberNexusConfiguration->setExpressionEnabled(true);
            $emberNexusConfiguration->setExpressionMaxLength(250);
            $emberNexusConfiguration->setExpressionWarningLength(100);
        }

        if ($simulateTimeout) {
            // overridden so the timeout branch can be verified without actually waiting on a real clock
            return new class($authProvider, $emberNexusConfiguration, $logger ?? $this->prophesize(LoggerInterface::class)->reveal(), $client400BadContentExceptionFactory, $client403ForbiddenExceptionFactory, $client422UnprocessableContentExceptionFactory) extends ExpressionService {
                protected function runProcess(Process $process): void
                {
                    throw new ProcessTimedOutException($process, ProcessTimedOutException::TYPE_GENERAL);
                }
            };
        }

        return new ExpressionService(
            $authProvider,
            $emberNexusConfiguration,
            $logger ?? $this->prophesize(LoggerInterface::class)->reveal(),
            $client400BadContentExceptionFactory,
            $client403ForbiddenExceptionFactory,
            $client422UnprocessableContentExceptionFactory
        );
    }

    public static function validExpressionProvider(): array
    {
        return [
            // simple scalar values
            ['1', null, 1],
            ['true', null, true],
            ['false', null, false],
            ['"{{ some string }}"', null, '{{ some string }}'],
            ['"string"', null, 'string'],
            ['["a", "b", "c"]', null, ['a', 'b', 'c']],
            ['{"name": "John", "age": 30}', null, ['name' => 'John', 'age' => 30]],

            // simple in place expressions
            ['1 + 2', null, 3],
            ['"some" + " string"', null, 'some string'],
            ['trim("   hello world! :D    ")', null, 'hello world! :D'],

            // parameterized expressions
            ['elementIds', ['elementIds' => ['a', 'b', 'c']], ['a', 'b', 'c']],
            ['map(elementIds, # | upper() + "-test")', ['elementIds' => ['a', 'b', 'c']], ['A-test', 'B-test', 'C-test']],

            // check that env is empty by default -> not leaking actual environment variables
            ['$env', null, []],
            ['$env', ['a' => 123], ['a' => 123]],
        ];
    }

    #[DataProvider('validExpressionProvider')]
    public function testValidExpressionsReturnValidResults(string $expression, ?array $parameters, mixed $result): void
    {
        $expressionService = $this->getExpressionService();
        $expressionResult = $expressionService->runExpression($expression, $parameters);

        $this->assertEquals($result, $expressionResult);
    }

    public static function emptyLikeParametersProvider(): array
    {
        return [
            ['1', null, 1],
            ['1', [], 1],
            ['1', json_decode('null', true), 1],
            ['1', json_decode('[]', true), 1],
            ['1', json_decode('{}', true), 1],
            ['1', json_decode('{"key": "value"}', true), 1],
        ];
    }

    #[DataProvider('emptyLikeParametersProvider')]
    public function testEmptyLikeParametersDoNotThrowException(string $expression, ?array $parameters, mixed $result): void
    {
        $expressionService = $this->getExpressionService();
        $expressionResult = $expressionService->runExpression($expression, $parameters);

        $this->assertEquals($result, $expressionResult);
    }

    public function testExpressionIsLogged(): void
    {
        $logger = TestLogger::create();
        $expressionService = $this->getExpressionService(
            logger: $logger
        );
        $expressionService->runExpression('"some expression"');

        $this->assertLogHappened(
            $logger,
            'info',
            'Executing expression.',
            [
                'expression_truncated' => '"some expression"',
                'expression_length' => 17,
                'user' => '3f34b034-bbe4-4461-a023-dc2920c1b1c3',
            ]
        );
    }

    public function testTooLongExpressionThrowsException(): void
    {
        $logger = TestLogger::create();
        $expressionService = $this->getExpressionService(
            logger: $logger
        );

        $validStringLength = sprintf(
            '"%s"',
            str_repeat('a', 250 - 2)
        );
        $expressionResult = $expressionService->runExpression($validStringLength);
        $this->assertIsString($expressionResult);

        $invalidStringLength = sprintf(
            '"%s"',
            str_repeat('a', 250 - 2 + 1)
        );
        $exception = null;
        try {
            $expressionService->runExpression($invalidStringLength);
        } catch (Throwable $t) {
            $exception = $t;
        }
        $this->assertInstanceOf(Client400BadContentException::class, $exception);
        $this->assertSame('Found expression with length 251, exceeded max length of 250.', $exception->getDetail());
        $this->assertLogHappened(
            $logger,
            'notice',
            'Expression exceeded max length.',
            [
                'expression_truncated' => '"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"',
                'expression_length' => 251,
                'user' => '3f34b034-bbe4-4461-a023-dc2920c1b1c3',
            ]
        );
    }

    public function testLongerExpressionIsLoggedAsWarning(): void
    {
        $logger = TestLogger::create();
        $expressionService = $this->getExpressionService(
            logger: $logger
        );

        $shortExpression = sprintf(
            '"%s"',
            str_repeat('b', 100 - 2)
        );
        $expressionResult = $expressionService->runExpression($shortExpression);
        $this->assertIsString($expressionResult);
        $this->assertLogNotHappened($logger, 'qdqwdw');

        $longerExpression = sprintf(
            '"%s"',
            str_repeat('a', 100 - 2 + 1)
        );
        $expressionResult = $expressionService->runExpression($longerExpression);
        $this->assertIsString($expressionResult);
        $this->assertLogHappened(
            $logger,
            'warning',
            'Expression exceeds warning length.',
            [
                'expression_truncated' => '"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"',
                'expression_length' => 101,
                'user' => '3f34b034-bbe4-4461-a023-dc2920c1b1c3',
            ]
        );
    }

    public function testExpressionThrowsExceptionWhenDisabled(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setExpressionEnabled(false);
        $expressionService = $this->getExpressionService(
            emberNexusConfiguration: $emberNexusConfiguration
        );

        $exception = null;
        try {
            $expressionService->runExpression('1');
        } catch (Throwable $t) {
            $exception = $t;
        }
        $this->assertInstanceOf(Client403ForbiddenException::class, $exception);
        $this->assertSame('Expressions are disabled.', $exception->getDetail());
    }

    public function testBrokenExpressionThrowsException(): void
    {
        $expressionService = $this->getExpressionService();

        $exception = null;
        try {
            $expressionService->runExpression('1 + a');
        } catch (Throwable $t) {
            $exception = $t;
        }
        $this->assertInstanceOf(Client400BadContentException::class, $exception);
        $this->assertSame("Expression execution failed.\n\ncompile error: unknown name a (1:5)\n | 1 + a\n | ....^", $exception->getDetail());
    }

    public function testShortQueryWithinTimeoutSucceeds(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setExpressionEnabled(true);
        $emberNexusConfiguration->setExpressionMaxLength(1000);
        $emberNexusConfiguration->setExpressionWarningLength(1000);
        $emberNexusConfiguration->setExpressionTimeoutInMilliseconds(2000);
        $expressionService = $this->getExpressionService(
            emberNexusConfiguration: $emberNexusConfiguration
        );

        $result = $expressionService->runExpression('len(map(1..1000, # * 2))');

        $this->assertSame(1000, $result);
    }

    public function testExceededTimeoutThrowsClient422UnprocessableContentException(): void
    {
        $logger = TestLogger::create();
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setExpressionEnabled(true);
        $emberNexusConfiguration->setExpressionMaxLength(1000);
        $emberNexusConfiguration->setExpressionWarningLength(1000);
        $emberNexusConfiguration->setExpressionTimeoutInMilliseconds(500);
        $expressionService = $this->getExpressionService(
            emberNexusConfiguration: $emberNexusConfiguration,
            logger: $logger,
            simulateTimeout: true
        );

        $exception = null;
        try {
            $expressionService->runExpression('1');
        } catch (Throwable $t) {
            $exception = $t;
        }
        $this->assertInstanceOf(Client422UnprocessableContentException::class, $exception);
        $this->assertSame('Query execution timed out after 500 ms.', $exception->getDetail());
        $this->assertLogHappened(
            $logger,
            'notice',
            'Expression execution timed out.',
            [
                'expression_truncated' => '1',
                'timeout_in_milliseconds' => 500,
                'user' => '3f34b034-bbe4-4461-a023-dc2920c1b1c3',
            ]
        );
    }
}
