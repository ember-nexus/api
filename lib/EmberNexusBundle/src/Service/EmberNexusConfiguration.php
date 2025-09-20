<?php

declare(strict_types=1);

namespace EmberNexusBundle\Service;

use Exception;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EmberNexusConfiguration
{
    public const string PAGE_SIZE = 'pageSize';
    public const string PAGE_SIZE_MIN = 'min';
    public const string PAGE_SIZE_DEFAULT = 'default';
    public const string PAGE_SIZE_MAX = 'max';

    public const string REGISTER = 'register';
    public const string REGISTER_ENABLED = 'enabled';
    public const string REGISTER_UNIQUE_IDENTIFIER = 'uniqueIdentifier';
    public const string REGISTER_UNIQUE_IDENTIFIER_REGEX = 'uniqueIdentifierRegex';

    public const string EXPRESSION = 'expression';
    public const string EXPRESSION_ENABLED = 'enabled';
    public const string EXPRESSION_WARNING_LENGTH = 'warningLength';
    public const string EXPRESSION_MAX_LENGTH = 'maxLength';

    public const string INSTANCE_CONFIGURATION = 'instanceConfiguration';
    public const string INSTANCE_CONFIGURATION_ENABLED = 'enabled';
    public const string INSTANCE_CONFIGURATION_SHOW_VERSION = 'showVersion';

    public const string TOKEN = 'token';
    public const string TOKEN_MIN_LIFETIME_IN_SECONDS = 'minLifetimeInSeconds';
    public const string TOKEN_DEFAULT_LIFETIME_IN_SECONDS = 'defaultLifetimeInSeconds';
    public const string TOKEN_MAX_LIFETIME_IN_SECONDS = 'maxLifetimeInSeconds';
    public const string TOKEN_DELETE_EXPIRED_TOKENS_AUTOMATICALLY_IN_SECONDS = 'tokenDeleteExpiredTokensAutomaticallyInSeconds';
    public const string CACHE = 'cache';
    public const string CACHE_ETAG_SEED = 'etagSeed';
    public const string CACHE_ETAG_UPPER_LIMIT_IN_COLLECTION_ENDPOINTS = 'etagUpperLimitInCollectionEndpoints';

    public const string FEATURE_FLAG = 'featureFlag';
    public const string FEATURE_FLAG_280_OLD_UNIQUE_USER_IDENTIFIER_DISABLED = '280_OldUniqueUserIdentifierDisabled';

    private int $pageSizeMin;
    private int $pageSizeDefault;
    private int $pageSizeMax;
    private bool $registerEnabled;
    private string $registerUniqueIdentifier;
    private string|false $registerUniqueIdentifierRegex;
    private bool $expressionEnabled;
    private int $expressionWarningLength;
    private int $expressionMaxLength;
    private bool $instanceConfigurationEnabled;
    private bool $instanceConfigurationShowVersion;
    private int $tokenMinLifetimeInSeconds;
    private int $tokenDefaultLifetimeInSeconds;
    private int|false $tokenMaxLifetimeInSeconds;
    private int|false $tokenDeleteExpiredTokensAutomaticallyInSeconds;
    private string $cacheEtagSeed;
    private int $cacheEtagUpperLimitInCollectionEndpoints;
    private bool $featureFlag280OldUniqueUserIdentifierDisabled;

    private static function getValueFromConfig(array $configuration, array $keyParts): mixed
    {
        $currentKeyParts = [];
        foreach ($keyParts as $keyPart) {
            $currentKeyParts[] = $keyPart;
            if (!array_key_exists($keyPart, $configuration)) {
                throw new Exception(sprintf("Configuration must contain key '%s'.", implode('.', $currentKeyParts)));
            }
            $configuration = $configuration[$keyPart];
        }

        return $configuration;
    }

    public static function createFromConfiguration(array $configuration): self
    {
        $emberNexusConfiguration = new self();

        $emberNexusConfiguration->setPageSizeMin((int) self::getValueFromConfig(
            $configuration,
            [
                self::PAGE_SIZE,
                self::PAGE_SIZE_MIN,
            ]
        ));
        $emberNexusConfiguration->setPageSizeDefault((int) self::getValueFromConfig(
            $configuration,
            [
                self::PAGE_SIZE,
                self::PAGE_SIZE_DEFAULT,
            ]
        ));
        $emberNexusConfiguration->setPageSizeMax((int) self::getValueFromConfig(
            $configuration,
            [
                self::PAGE_SIZE,
                self::PAGE_SIZE_MAX,
            ]
        ));

        if ($emberNexusConfiguration->getPageSizeMax() < $emberNexusConfiguration->getPageSizeMin()) {
            throw new Exception('pagesize max must be smaller or equal to pagesize min.');
        }
        if ($emberNexusConfiguration->getPageSizeDefault() < $emberNexusConfiguration->getPageSizeMin()) {
            throw new Exception('default page size must be at least as big as min pagesize');
        }
        if ($emberNexusConfiguration->getPageSizeMax() < $emberNexusConfiguration->getPageSizeDefault()) {
            throw new Exception('default page size must be equal or less than max page size.');
        }

        $emberNexusConfiguration->setRegisterEnabled((bool) self::getValueFromConfig(
            $configuration,
            [
                self::REGISTER,
                self::REGISTER_ENABLED,
            ]
        ));
        $emberNexusConfiguration->setRegisterUniqueIdentifier((string) self::getValueFromConfig(
            $configuration,
            [
                self::REGISTER,
                self::REGISTER_UNIQUE_IDENTIFIER,
            ]
        ));
        $value = self::getValueFromConfig(
            $configuration,
            [
                self::REGISTER,
                self::REGISTER_UNIQUE_IDENTIFIER_REGEX,
            ]
        );
        if (false !== $value) {
            $value = (string) $value;
        }
        $emberNexusConfiguration->setRegisterUniqueIdentifierRegex($value);

        $emberNexusConfiguration->setExpressionEnabled((bool) self::getValueFromConfig(
            $configuration,
            [
                self::EXPRESSION,
                self::EXPRESSION_ENABLED,
            ]
        ));
        $emberNexusConfiguration->setExpressionWarningLength((int) self::getValueFromConfig(
            $configuration,
            [
                self::EXPRESSION,
                self::EXPRESSION_WARNING_LENGTH,
            ]
        ));
        $emberNexusConfiguration->setExpressionMaxLength((int) self::getValueFromConfig(
            $configuration,
            [
                self::EXPRESSION,
                self::EXPRESSION_MAX_LENGTH,
            ]
        ));

        $emberNexusConfiguration->setInstanceConfigurationEnabled((bool) self::getValueFromConfig(
            $configuration,
            [
                self::INSTANCE_CONFIGURATION,
                self::INSTANCE_CONFIGURATION_ENABLED,
            ]
        ));
        $emberNexusConfiguration->setInstanceConfigurationShowVersion((bool) self::getValueFromConfig(
            $configuration,
            [
                self::INSTANCE_CONFIGURATION,
                self::INSTANCE_CONFIGURATION_SHOW_VERSION,
            ]
        ));

        $emberNexusConfiguration->setTokenMinLifetimeInSeconds((int) self::getValueFromConfig(
            $configuration,
            [
                self::TOKEN,
                self::TOKEN_MIN_LIFETIME_IN_SECONDS,
            ]
        ));
        $emberNexusConfiguration->setTokenDefaultLifetimeInSeconds((int) self::getValueFromConfig(
            $configuration,
            [
                self::TOKEN,
                self::TOKEN_DEFAULT_LIFETIME_IN_SECONDS,
            ]
        ));
        $value = self::getValueFromConfig(
            $configuration,
            [
                self::TOKEN,
                self::TOKEN_MAX_LIFETIME_IN_SECONDS,
            ]
        );
        $emberNexusConfiguration->setTokenMaxLifetimeInSeconds($value);
        $value = self::getValueFromConfig(
            $configuration,
            [
                self::TOKEN,
                self::TOKEN_DELETE_EXPIRED_TOKENS_AUTOMATICALLY_IN_SECONDS,
            ]
        );
        if (false !== $value) {
            $value = (int) $value;
        }
        $emberNexusConfiguration->setTokenDeleteExpiredTokensAutomaticallyInSeconds($value);

        if (false !== $emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
            if ($emberNexusConfiguration->getTokenMaxLifetimeInSeconds() < $emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
                throw new Exception('token max lifetime must be longer than min lifetime.');
            }
            if ($emberNexusConfiguration->getTokenDefaultLifetimeInSeconds() > $emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
                throw new Exception('Token default lifetime must by shorter or equal to max lifetime.');
            }
        }
        if ($emberNexusConfiguration->getTokenDefaultLifetimeInSeconds() < $emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
            throw new Exception('token default lifetime must be equal or longer to min lifetime.');
        }

        $value = self::getValueFromConfig(
            $configuration,
            [
                self::CACHE,
                self::CACHE_ETAG_SEED,
            ]
        );
        $emberNexusConfiguration->setCacheEtagSeed($value);

        $value = self::getValueFromConfig(
            $configuration,
            [
                self::CACHE,
                self::CACHE_ETAG_UPPER_LIMIT_IN_COLLECTION_ENDPOINTS,
            ]
        );
        $emberNexusConfiguration->setCacheEtagUpperLimitInCollectionEndpoints($value);

        $value = self::getValueFromConfig(
            $configuration,
            [
                self::FEATURE_FLAG,
                self::FEATURE_FLAG_280_OLD_UNIQUE_USER_IDENTIFIER_DISABLED,
            ]
        );
        $emberNexusConfiguration->setFeatureFlag280OldUniqueUserIdentifierDisabled($value);

        return $emberNexusConfiguration;
    }

    public function getPageSizeMin(): int
    {
        return $this->pageSizeMin;
    }

    public function setPageSizeMin(int $pageSizeMin): self
    {
        $this->pageSizeMin = $pageSizeMin;

        return $this;
    }

    public function getPageSizeDefault(): int
    {
        return $this->pageSizeDefault;
    }

    public function setPageSizeDefault(int $pageSizeDefault): self
    {
        $this->pageSizeDefault = $pageSizeDefault;

        return $this;
    }

    public function getPageSizeMax(): int
    {
        return $this->pageSizeMax;
    }

    public function setPageSizeMax(int $pageSizeMax): self
    {
        $this->pageSizeMax = $pageSizeMax;

        return $this;
    }

    public function isRegisterEnabled(): bool
    {
        return $this->registerEnabled;
    }

    public function setRegisterEnabled(bool $registerEnabled): self
    {
        $this->registerEnabled = $registerEnabled;

        return $this;
    }

    public function getRegisterUniqueIdentifier(): string
    {
        return $this->registerUniqueIdentifier;
    }

    public function setRegisterUniqueIdentifier(string $registerUniqueIdentifier): self
    {
        if (0 === strlen($registerUniqueIdentifier)) {
            throw new Exception('Unique identifier can not be an empty string.');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $registerUniqueIdentifier)) {
            throw new Exception("Unique identifier must only contain alphanumeric characters and '_'.");
        }
        $this->registerUniqueIdentifier = $registerUniqueIdentifier;

        return $this;
    }

    public function getRegisterUniqueIdentifierRegex(): string|false
    {
        return $this->registerUniqueIdentifierRegex;
    }

    public function setRegisterUniqueIdentifierRegex(string|false $registerUniqueIdentifierRegex): self
    {
        $this->registerUniqueIdentifierRegex = $registerUniqueIdentifierRegex;

        return $this;
    }

    public function isExpressionEnabled(): bool
    {
        return $this->expressionEnabled;
    }

    public function setExpressionEnabled(bool $expressionEnabled): self
    {
        $this->expressionEnabled = $expressionEnabled;

        return $this;
    }

    public function getExpressionWarningLength(): int
    {
        return $this->expressionWarningLength;
    }

    public function setExpressionWarningLength(int $expressionWarningLength): self
    {
        $this->expressionWarningLength = $expressionWarningLength;

        return $this;
    }

    public function getExpressionMaxLength(): int
    {
        return $this->expressionMaxLength;
    }

    public function setExpressionMaxLength(int $expressionMaxLength): self
    {
        $this->expressionMaxLength = $expressionMaxLength;

        return $this;
    }

    public function isInstanceConfigurationEnabled(): bool
    {
        return $this->instanceConfigurationEnabled;
    }

    public function setInstanceConfigurationEnabled(bool $instanceConfigurationEnabled): self
    {
        $this->instanceConfigurationEnabled = $instanceConfigurationEnabled;

        return $this;
    }

    public function isInstanceConfigurationShowVersion(): bool
    {
        return $this->instanceConfigurationShowVersion;
    }

    public function setInstanceConfigurationShowVersion(bool $instanceConfigurationShowVersion): self
    {
        $this->instanceConfigurationShowVersion = $instanceConfigurationShowVersion;

        return $this;
    }

    public function getTokenMinLifetimeInSeconds(): int
    {
        return $this->tokenMinLifetimeInSeconds;
    }

    public function setTokenMinLifetimeInSeconds(int $tokenMinLifetimeInSeconds): self
    {
        $this->tokenMinLifetimeInSeconds = $tokenMinLifetimeInSeconds;

        return $this;
    }

    public function getTokenDefaultLifetimeInSeconds(): int
    {
        return $this->tokenDefaultLifetimeInSeconds;
    }

    public function setTokenDefaultLifetimeInSeconds(int $tokenDefaultLifetimeInSeconds): self
    {
        $this->tokenDefaultLifetimeInSeconds = $tokenDefaultLifetimeInSeconds;

        return $this;
    }

    public function getTokenMaxLifetimeInSeconds(): false|int
    {
        return $this->tokenMaxLifetimeInSeconds;
    }

    public function setTokenMaxLifetimeInSeconds(bool|int $tokenMaxLifetimeInSeconds): self
    {
        $this->tokenMaxLifetimeInSeconds = $tokenMaxLifetimeInSeconds;

        return $this;
    }

    public function getTokenDeleteExpiredTokensAutomaticallyInSeconds(): bool|int
    {
        return $this->tokenDeleteExpiredTokensAutomaticallyInSeconds;
    }

    public function setTokenDeleteExpiredTokensAutomaticallyInSeconds(bool|int $tokenDeleteExpiredTokensAutomaticallyInSeconds): self
    {
        $this->tokenDeleteExpiredTokensAutomaticallyInSeconds = $tokenDeleteExpiredTokensAutomaticallyInSeconds;

        return $this;
    }

    public function getCacheEtagSeed(): string
    {
        return $this->cacheEtagSeed;
    }

    public function setCacheEtagSeed(string $cacheEtagSeed): self
    {
        $this->cacheEtagSeed = $cacheEtagSeed;

        return $this;
    }

    public function getCacheEtagUpperLimitInCollectionEndpoints(): int
    {
        return $this->cacheEtagUpperLimitInCollectionEndpoints;
    }

    public function setCacheEtagUpperLimitInCollectionEndpoints(int $cacheEtagUpperLimitInCollectionEndpoints): self
    {
        $this->cacheEtagUpperLimitInCollectionEndpoints = $cacheEtagUpperLimitInCollectionEndpoints;

        return $this;
    }

    public function isFeatureFlag280OldUniqueUserIdentifierDisabled(): bool
    {
        return $this->featureFlag280OldUniqueUserIdentifierDisabled;
    }

    public function setFeatureFlag280OldUniqueUserIdentifierDisabled(bool $featureFlag280OldUniqueUserIdentifierDisabled): self
    {
        $this->featureFlag280OldUniqueUserIdentifierDisabled = $featureFlag280OldUniqueUserIdentifierDisabled;

        return $this;
    }
}
