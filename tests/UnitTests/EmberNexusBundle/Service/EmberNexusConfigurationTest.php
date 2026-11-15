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

    /**
     * @return array<array-key, mixed>
     */
    private function processDefaultConfiguration(): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [[]]);
    }

    public function testCreateFromConfigurationAcceptsNumericStrings(): void
    {
        $configuration = $this->processDefaultConfiguration();
        $configuration[EmberNexusConfiguration::PAGE_SIZE][EmberNexusConfiguration::PAGE_SIZE_MIN] = '5';

        $emberNexusConfiguration = EmberNexusConfiguration::createFromConfiguration($configuration);

        $this->assertSame(5, $emberNexusConfiguration->getPageSizeMin());
    }

    public function testCreateFromConfigurationRejectsNonNumericInteger(): void
    {
        $configuration = $this->processDefaultConfiguration();
        $configuration[EmberNexusConfiguration::PAGE_SIZE][EmberNexusConfiguration::PAGE_SIZE_MIN] = 'abc';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Configuration key 'pageSize.min' must be int, got string.");
        EmberNexusConfiguration::createFromConfiguration($configuration);
    }

    public function testCreateFromConfigurationRejectsNonScalarString(): void
    {
        $configuration = $this->processDefaultConfiguration();
        $configuration[EmberNexusConfiguration::CACHE][EmberNexusConfiguration::CACHE_ETAG_SEED] = ['not', 'a', 'string'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Configuration key 'cache.etagSeed' must be string, got array.");
        EmberNexusConfiguration::createFromConfiguration($configuration);
    }

    public function testCreateFromConfigurationRejectsMissingKey(): void
    {
        $configuration = $this->processDefaultConfiguration();
        unset($configuration[EmberNexusConfiguration::PAGE_SIZE][EmberNexusConfiguration::PAGE_SIZE_MIN]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Configuration must contain key 'pageSize.min'.");
        EmberNexusConfiguration::createFromConfiguration($configuration);
    }

    public function testCreateFromConfigurationRejectsMissingSection(): void
    {
        $configuration = $this->processDefaultConfiguration();
        unset($configuration[EmberNexusConfiguration::PAGE_SIZE]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Configuration must contain key 'pageSize'.");
        EmberNexusConfiguration::createFromConfiguration($configuration);
    }

    public function testCreateFromConfigurationAcceptsFalseForOptionalTokenLifetimes(): void
    {
        $configuration = $this->processDefaultConfiguration();
        $configuration[EmberNexusConfiguration::TOKEN][EmberNexusConfiguration::TOKEN_MAX_LIFETIME_IN_SECONDS] = false;
        $configuration[EmberNexusConfiguration::TOKEN][EmberNexusConfiguration::TOKEN_DELETE_EXPIRED_TOKENS_AUTOMATICALLY_IN_SECONDS] = false;

        $emberNexusConfiguration = EmberNexusConfiguration::createFromConfiguration($configuration);

        $this->assertFalse($emberNexusConfiguration->getTokenMaxLifetimeInSeconds());
        $this->assertFalse($emberNexusConfiguration->getTokenDeleteExpiredTokensAutomaticallyInSeconds());
    }
}
