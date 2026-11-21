<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Client422UnprocessableContentExceptionFactory;
use App\Security\AuthProvider;
use App\Service\QueryExplanation\ExprLang\ExprLangQueryExplanationService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ExpressionService
{
    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private LoggerInterface $logger,
        private ExprLangQueryExplanationService $exprLangQueryExplanationService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private Client422UnprocessableContentExceptionFactory $client422UnprocessableContentExceptionFactory,
    ) {
    }

    private function validateExpression(string $expression): void
    {
        $expressionLength = strlen($expression);
        if ($expressionLength > $this->emberNexusConfiguration->getExpressionMaxLength()) {
            $this->logger->notice(
                'Expression exceeded max length.',
                [
                    'expression_truncated' => substr($expression, 0, 256),
                    'expression_length' => $expressionLength,
                    'user' => $this->authProvider->getUserId()->toString(),
                ]
            );
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Found expression with length %d, exceeded max length of %d.', $expressionLength, $this->emberNexusConfiguration->getExpressionMaxLength()));
        }
        if ($expressionLength > $this->emberNexusConfiguration->getExpressionWarningLength()) {
            $this->logger->warning(
                'Expression exceeds warning length.',
                [
                    'expression_truncated' => substr($expression, 0, 256),
                    'expression_length' => $expressionLength,
                    'user' => $this->authProvider->getUserId()->toString(),
                ]
            );
        }
        $this->logger->info(
            'Executing expression.',
            [
                'expression_truncated' => substr($expression, 0, 256),
                'expression_length' => $expressionLength,
                'user' => $this->authProvider->getUserId()->toString(),
            ]
        );
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    public function runExpression(string $expression, ?array $parameters = null): mixed
    {
        if (!$this->emberNexusConfiguration->isExpressionEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate('Expressions are disabled.');
        }
        $this->validateExpression($expression);

        if (null === $parameters) {
            $parameters = new stdClass();
        } elseif (0 === count($parameters)) {
            $parameters = new stdClass();
        }

        $runtimePayload = [
            'expression' => $expression,
            'parameters' => $parameters,
        ];
        $runtimePayload = \Safe\json_encode($runtimePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $tempFile = \Safe\tempnam(sys_get_temp_dir(), 'expr_cli_');
        \Safe\file_put_contents($tempFile, $runtimePayload);

        $timeoutInMilliseconds = $this->emberNexusConfiguration->getExpressionTimeoutInMilliseconds();

        $process = new Process(['/usr/local/bin/expr-cli', '-f', $tempFile]);
        if (null !== $timeoutInMilliseconds) {
            $process->setTimeout($timeoutInMilliseconds / 1000);
        }

        try {
            $this->runProcess($process);
        } catch (ProcessTimedOutException) {
            \Safe\unlink($tempFile);
            $this->logger->notice(
                'Expression execution timed out.',
                [
                    'expression_truncated' => substr($expression, 0, 256),
                    'timeout_in_milliseconds' => $timeoutInMilliseconds,
                    'user' => $this->authProvider->getUserId()->toString(),
                ]
            );
            throw $this->client422UnprocessableContentExceptionFactory->createFromExpressionTimeout((int) $timeoutInMilliseconds);
        }
        \Safe\unlink($tempFile);

        if (!$process->isSuccessful()) {
            if (!$this->emberNexusConfiguration->isSearchExplainFailures()) {
                throw $this->client400BadContentExceptionFactory->createFromDetail('Expression execution failed.');
            }
            $errorOutput = $process->getErrorOutput();
            $explanation = $this->exprLangQueryExplanationService->explain($errorOutput);
            /** @psalm-suppress MixedAssignment */
            $diagnosticsMessage = $explanation->diagnostics['message'] ?? null;
            $detailErrorMessage = is_string($diagnosticsMessage) ? $diagnosticsMessage : 'unknown error';
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Expression execution failed.\n\n%s", $detailErrorMessage), additionalProperties: ['expressionError' => ['source' => $explanation->source, 'code' => $explanation->code, 'diagnostics' => $explanation->diagnostics]]);
        }

        $result = $process->getOutput();

        return \Safe\json_decode($result, true);
    }

    /**
     * Isolated as its own seam so tests can simulate a timeout without waiting on a real clock.
     *
     * @throws ProcessTimedOutException
     */
    protected function runProcess(Process $process): void
    {
        $process->run();
    }
}
