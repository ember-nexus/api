<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EmberNexusBundle\Service;

use EmberNexusBundle\DependencyInjection\Configuration;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

#[Small]
#[CoversClass(EmberNexusConfiguration::class)]
class EmberNexusConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private function createFromConfig(array $config): EmberNexusConfiguration
    {
        $processedConfiguration = (new Processor())->processConfiguration(new Configuration(), [$config]);

        return EmberNexusConfiguration::createFromConfiguration($processedConfiguration);
    }

    public function testExpressionTimeoutDefaultsTo5000Milliseconds(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([]);

        $this->assertSame(5000, $emberNexusConfiguration->getExpressionTimeoutInMilliseconds());
    }

    public function testExpressionTimeoutCanBeOverwritten(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([
            EmberNexusConfiguration::EXPRESSION => [
                EmberNexusConfiguration::EXPRESSION_TIMEOUT_IN_MILLISECONDS => 1234,
            ],
        ]);

        $this->assertSame(1234, $emberNexusConfiguration->getExpressionTimeoutInMilliseconds());
    }

    public function testExpressionTimeoutCanBeSetToNull(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([
            EmberNexusConfiguration::EXPRESSION => [
                EmberNexusConfiguration::EXPRESSION_TIMEOUT_IN_MILLISECONDS => null,
            ],
        ]);

        $this->assertNull($emberNexusConfiguration->getExpressionTimeoutInMilliseconds());
    }

    public function testExpressionTimeoutBelowMinimumIsRejectedDuringConfigurationCreation(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expression timeout must be null or at least 100 milliseconds.');

        $this->createFromConfig([
            EmberNexusConfiguration::EXPRESSION => [
                EmberNexusConfiguration::EXPRESSION_TIMEOUT_IN_MILLISECONDS => 99,
            ],
        ]);
    }

    public function testSetExpressionTimeoutInMillisecondsAcceptsNull(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setExpressionTimeoutInMilliseconds(null);

        $this->assertNull($emberNexusConfiguration->getExpressionTimeoutInMilliseconds());
    }

    public function testSetExpressionTimeoutInMillisecondsAcceptsMinimumValue(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setExpressionTimeoutInMilliseconds(EmberNexusConfiguration::EXPRESSION_MIN_TIMEOUT_IN_MILLISECONDS);

        $this->assertSame(100, $emberNexusConfiguration->getExpressionTimeoutInMilliseconds());
    }

    public function testSetExpressionTimeoutInMillisecondsRejectsValueBelowMinimum(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expression timeout must be null or at least 100 milliseconds.');

        $emberNexusConfiguration->setExpressionTimeoutInMilliseconds(99);
    }

    public function testRegisterUniqueIdentifierRegexDefaultsToLooseEmailCheck(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([]);

        $this->assertSame('/^[^\s@]+@[^\s@]+$/', $emberNexusConfiguration->getRegisterUniqueIdentifierRegex());
    }

    public function testRegisterUniqueIdentifierRegexCanBeDisabled(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([
            EmberNexusConfiguration::REGISTER => [
                EmberNexusConfiguration::REGISTER_UNIQUE_IDENTIFIER_REGEX => false,
            ],
        ]);

        $this->assertFalse($emberNexusConfiguration->getRegisterUniqueIdentifierRegex());
    }

    public function testRegisterUniqueIdentifierRegexCanBeOverwritten(): void
    {
        $emberNexusConfiguration = $this->createFromConfig([
            EmberNexusConfiguration::REGISTER => [
                EmberNexusConfiguration::REGISTER_UNIQUE_IDENTIFIER_REGEX => '/^[0-9]+$/',
            ],
        ]);

        $this->assertSame('/^[0-9]+$/', $emberNexusConfiguration->getRegisterUniqueIdentifierRegex());
    }

    #[WithoutErrorHandler]
    public function testInvalidRegisterUniqueIdentifierRegexIsRejectedDuringConfigurationCreation(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unique identifier regex '/this is not valid(/' is not a valid regular expression.");

        $this->createFromConfig([
            EmberNexusConfiguration::REGISTER => [
                EmberNexusConfiguration::REGISTER_UNIQUE_IDENTIFIER_REGEX => '/this is not valid(/',
            ],
        ]);
    }

    public function testSetRegisterUniqueIdentifierRegexAcceptsFalse(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setRegisterUniqueIdentifierRegex(false);

        $this->assertFalse($emberNexusConfiguration->getRegisterUniqueIdentifierRegex());
    }

    public function testSetRegisterUniqueIdentifierRegexAcceptsValidPattern(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setRegisterUniqueIdentifierRegex('/^[0-9]+$/');

        $this->assertSame('/^[0-9]+$/', $emberNexusConfiguration->getRegisterUniqueIdentifierRegex());
    }

    #[WithoutErrorHandler]
    public function testSetRegisterUniqueIdentifierRegexRejectsInvalidPattern(): void
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unique identifier regex '/this is not valid(/' is not a valid regular expression.");

        $emberNexusConfiguration->setRegisterUniqueIdentifierRegex('/this is not valid(/');
    }
}
