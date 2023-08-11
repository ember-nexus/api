<?php

namespace EmberNexusBundle\Service;

class EmberNexusConfiguration
{
    private int $pageSizeMin;
    private int $pageSizeDefault;
    private int $pageSizeMax;
    private bool $registerEnabled;
    private string $registerUniqueIdentifier;
    private ?string $registerUniqueIdentifierRegex;
    private bool $instanceConfigurationEnabled;
    private bool $instanceConfigurationShowVersion;
    private int $tokenMinLifetimeInSeconds;
    private int $tokenDefaultLifetimeInSeconds;
    private int|false $tokenMaxLifetimeInSeconds;
    private int|false $tokenDeleteExpiredTokensAutomaticallyInSeconds;

    public static function createFromConfiguration(array $configuration): self
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();

        if (!array_key_exists('pageSize', $configuration)) {
            throw new \Exception("Configuration must contain key 'pageSize'.");
        }
        if (!array_key_exists('min', $configuration['pageSize'])) {
            throw new \Exception("Configuration must contain key 'pageSize.min'.");
        }
        $emberNexusConfiguration->setPageSizeMin((int) $configuration['pageSize']['min']);
        if (!array_key_exists('default', $configuration['pageSize'])) {
            throw new \Exception("Configuration must contain key 'pageSize.default'.");
        }
        $emberNexusConfiguration->setPageSizeDefault((int) $configuration['pageSize']['default']);
        if (!array_key_exists('max', $configuration['pageSize'])) {
            throw new \Exception("Configuration must contain key 'pageSize.max'.");
        }
        $emberNexusConfiguration->setPageSizeMax((int) $configuration['pageSize']['max']);

        if ($emberNexusConfiguration->getPageSizeMax() < $emberNexusConfiguration->getPageSizeMin()) {
            throw new \Exception('pagesize max must be smaller or equal to pagesize min.');
        }
        if ($emberNexusConfiguration->getPageSizeDefault() < $emberNexusConfiguration->getPageSizeMin()) {
            throw new \Exception('default page size must be at least as big as min pagesize');
        }
        if ($emberNexusConfiguration->getPageSizeMax() < $emberNexusConfiguration->getPageSizeDefault()) {
            throw new \Exception('default page size must be equal or less than max page size.');
        }

        if (!array_key_exists('register', $configuration)) {
            throw new \Exception("Configuration must contain key 'register'.");
        }
        if (!array_key_exists('enabled', $configuration['register'])) {
            throw new \Exception("Configuration must contain key 'register.enabled'.");
        }
        $emberNexusConfiguration->setRegisterEnabled((bool) $configuration['register']['enabled']);
        if (!array_key_exists('uniqueIdentifier', $configuration['register'])) {
            throw new \Exception("Configuration must contain key 'register.uniqueIdentifier'.");
        }
        $emberNexusConfiguration->setRegisterUniqueIdentifier((string) $configuration['register']['uniqueIdentifier']);
        if (!array_key_exists('uniqueIdentifierRegex', $configuration['register'])) {
            throw new \Exception("Configuration must contain key 'register.uniqueIdentifierRegex'.");
        }
        $emberNexusConfiguration->setRegisterUniqueIdentifierRegex((string) $configuration['register']['uniqueIdentifierRegex']);

        if (!array_key_exists('instanceConfiguration', $configuration)) {
            throw new \Exception("Configuration must contain key 'instanceConfiguration'.");
        }
        if (!array_key_exists('enabled', $configuration['instanceConfiguration'])) {
            throw new \Exception("Configuration must contain key 'instanceConfiguration.enabled'.");
        }
        $emberNexusConfiguration->setInstanceConfigurationEnabled((bool) $configuration['instanceConfiguration']['enabled']);
        if (!array_key_exists('showVersion', $configuration['instanceConfiguration'])) {
            throw new \Exception("Configuration must contain key 'instanceConfiguration.showVersion'.");
        }
        $emberNexusConfiguration->setInstanceConfigurationShowVersion((bool) $configuration['instanceConfiguration']['showVersion']);

        if (!array_key_exists('token', $configuration)) {
            throw new \Exception("Configuration must contain key 'token'.");
        }
        if (!array_key_exists('minLifetimeInSeconds', $configuration['token'])) {
            throw new \Exception("Configuration must contain key 'token.minLifetimeInSeconds'.");
        }
        $emberNexusConfiguration->setTokenMinLifetimeInSeconds((int) $configuration['token']['minLifetimeInSeconds']);
        if (!array_key_exists('defaultLifetimeInSeconds', $configuration['token'])) {
            throw new \Exception("Configuration must contain key 'token.defaultLifetimeInSeconds'.");
        }
        $emberNexusConfiguration->setTokenDefaultLifetimeInSeconds((int) $configuration['token']['defaultLifetimeInSeconds']);
        if (!array_key_exists('maxLifetimeInSeconds', $configuration['token'])) {
            throw new \Exception("Configuration must contain key 'token.maxLifetimeInSeconds'.");
        }
        $value = $configuration['token']['maxLifetimeInSeconds'];
        if (false !== $value) {
            $value = (int) $value;
        }
        $emberNexusConfiguration->setTokenMaxLifetimeInSeconds($value);
        if (!array_key_exists('deleteExpiredTokensAutomaticallyInSeconds', $configuration['token'])) {
            throw new \Exception("Configuration must contain key 'token.deleteExpiredTokensAutomaticallyInSeconds'.");
        }
        $value = $configuration['token']['deleteExpiredTokensAutomaticallyInSeconds'];
        if (false !== $value) {
            $value = (int) $value;
        }
        $emberNexusConfiguration->setTokenDeleteExpiredTokensAutomaticallyInSeconds($value);

        if (false !== $emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
            if ($emberNexusConfiguration->getTokenMaxLifetimeInSeconds() < $emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
                throw new \Exception('token max lifetime must be longer than min lifetime.');
            }
            if ($emberNexusConfiguration->getTokenDefaultLifetimeInSeconds() > $emberNexusConfiguration->getTokenMaxLifetimeInSeconds()) {
                throw new \Exception('Token default lifetime must by shorter or equal to max lifetime.');
            }
        }
        if ($emberNexusConfiguration->getTokenDefaultLifetimeInSeconds() < $emberNexusConfiguration->getTokenMinLifetimeInSeconds()) {
            throw new \Exception('token default lifetime must be equal or longer to min lifetime.');
        }

        return $emberNexusConfiguration;
    }

    public function getPageSizeMin(): int
    {
        return $this->pageSizeMin;
    }

    public function setPageSizeMin(int $pageSizeMin): EmberNexusConfiguration
    {
        $this->pageSizeMin = $pageSizeMin;

        return $this;
    }

    public function getPageSizeDefault(): int
    {
        return $this->pageSizeDefault;
    }

    public function setPageSizeDefault(int $pageSizeDefault): EmberNexusConfiguration
    {
        $this->pageSizeDefault = $pageSizeDefault;

        return $this;
    }

    public function getPageSizeMax(): int
    {
        return $this->pageSizeMax;
    }

    public function setPageSizeMax(int $pageSizeMax): EmberNexusConfiguration
    {
        $this->pageSizeMax = $pageSizeMax;

        return $this;
    }

    public function isRegisterEnabled(): bool
    {
        return $this->registerEnabled;
    }

    public function setRegisterEnabled(bool $registerEnabled): EmberNexusConfiguration
    {
        $this->registerEnabled = $registerEnabled;

        return $this;
    }

    public function getRegisterUniqueIdentifier(): string
    {
        return $this->registerUniqueIdentifier;
    }

    public function setRegisterUniqueIdentifier(string $registerUniqueIdentifier): EmberNexusConfiguration
    {
        if (0 === strlen($registerUniqueIdentifier)) {
            throw new \Exception('Unique identifier can not be an empty string.');
        }
        $this->registerUniqueIdentifier = $registerUniqueIdentifier;

        return $this;
    }

    public function getRegisterUniqueIdentifierRegex(): ?string
    {
        return $this->registerUniqueIdentifierRegex;
    }

    public function setRegisterUniqueIdentifierRegex(?string $registerUniqueIdentifierRegex): EmberNexusConfiguration
    {
        $this->registerUniqueIdentifierRegex = $registerUniqueIdentifierRegex;

        return $this;
    }

    public function isInstanceConfigurationEnabled(): bool
    {
        return $this->instanceConfigurationEnabled;
    }

    public function setInstanceConfigurationEnabled(bool $instanceConfigurationEnabled): EmberNexusConfiguration
    {
        $this->instanceConfigurationEnabled = $instanceConfigurationEnabled;

        return $this;
    }

    public function isInstanceConfigurationShowVersion(): bool
    {
        return $this->instanceConfigurationShowVersion;
    }

    public function setInstanceConfigurationShowVersion(bool $instanceConfigurationShowVersion): EmberNexusConfiguration
    {
        $this->instanceConfigurationShowVersion = $instanceConfigurationShowVersion;

        return $this;
    }

    public function getTokenMinLifetimeInSeconds(): int
    {
        return $this->tokenMinLifetimeInSeconds;
    }

    public function setTokenMinLifetimeInSeconds(int $tokenMinLifetimeInSeconds): EmberNexusConfiguration
    {
        $this->tokenMinLifetimeInSeconds = $tokenMinLifetimeInSeconds;

        return $this;
    }

    public function getTokenDefaultLifetimeInSeconds(): int
    {
        return $this->tokenDefaultLifetimeInSeconds;
    }

    public function setTokenDefaultLifetimeInSeconds(int $tokenDefaultLifetimeInSeconds): EmberNexusConfiguration
    {
        $this->tokenDefaultLifetimeInSeconds = $tokenDefaultLifetimeInSeconds;

        return $this;
    }

    public function getTokenMaxLifetimeInSeconds(): bool|int
    {
        return $this->tokenMaxLifetimeInSeconds;
    }

    public function setTokenMaxLifetimeInSeconds(bool|int $tokenMaxLifetimeInSeconds): EmberNexusConfiguration
    {
        $this->tokenMaxLifetimeInSeconds = $tokenMaxLifetimeInSeconds;

        return $this;
    }

    public function getTokenDeleteExpiredTokensAutomaticallyInSeconds(): bool|int
    {
        return $this->tokenDeleteExpiredTokensAutomaticallyInSeconds;
    }

    public function setTokenDeleteExpiredTokensAutomaticallyInSeconds(bool|int $tokenDeleteExpiredTokensAutomaticallyInSeconds): EmberNexusConfiguration
    {
        $this->tokenDeleteExpiredTokensAutomaticallyInSeconds = $tokenDeleteExpiredTokensAutomaticallyInSeconds;

        return $this;
    }
}
