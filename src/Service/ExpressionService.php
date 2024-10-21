<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AuthProvider;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Process\Process;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class ExpressionService
{
    public function __construct(
        private AuthProvider $authProvider,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private LoggerInterface $logger,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
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

        $process = new Process(['/usr/local/bin/expr-cli', '-f', $tempFile]);
        $process->run();
        \Safe\unlink($tempFile);

        if (!$process->isSuccessful()) {
            $stdOut = $process->getErrorOutput();
            $detailErrorMessage = 'unknown error';
            if ('' !== $stdOut) {
                $tmp = \Safe\json_decode($stdOut, true);
                if (array_key_exists('error', $tmp)) {
                    $detailErrorMessage = $tmp['error'];
                }
            }
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Expression execution failed.\n\n%s", $detailErrorMessage));
        }

        $result = $process->getOutput();

        return \Safe\json_decode($result, true);
    }
}
