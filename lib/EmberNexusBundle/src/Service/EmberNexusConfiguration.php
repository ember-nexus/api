<?php

declare(strict_types=1);

namespace EmberNexusBundle\Service;

use Exception;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
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

    public const string FILE = 'file';
    public const string FILE_MAX_FILE_SIZE_IN_BYTES = 'maxFileSizeInBytes';
    public const string FILE_UPLOAD_EXPIRES_IN_SECONDS_AFTER_FIRST_REQUEST = 'uploadExpiresInSecondsAfterFirstRequest';
    public const string FILE_UPLOAD_MIN_CHUNK_SIZE_IN_BYTES = 'uploadMinChunkSizeInBytes';
    public const string FILE_UPLOAD_MAX_CHUNK_SIZE_IN_BYTES = 'uploadMaxChunkSizeInBytes';
    public const string FILE_S3_STORAGE_BUCKET = 'S3StorageBucket';
    public const string FILE_S3_UPLOAD_BUCKET = 'S3UploadBucket';
    public const string FILE_S3_STORAGE_BUCKET_LEVELS = 'S3StorageBucketLevels';
    public const string FILE_S3_STORAGE_BUCKET_LEVEL_LENGTH = 'S3StorageBucketLevelLength';
    public const string FILE_S3_UPLOAD_BUCKET_LEVELS = 'S3UploadBucketLevels';
    public const string FILE_S3_UPLOAD_BUCKET_LEVEL_LENGTH = 'S3UploadBucketLevelLength';

    private int $pageSizeMin;
    private int $pageSizeDefault;
    private int $pageSizeMax;
    private bool $registerEnabled;
    private string $registerUniqueIdentifier;
    private string|false $registerUniqueIdentifierRegex;
    private bool $instanceConfigurationEnabled;
    private bool $instanceConfigurationShowVersion;
    private int $tokenMinLifetimeInSeconds;
    private int $tokenDefaultLifetimeInSeconds;
    private int|false $tokenMaxLifetimeInSeconds;
    private int|false $tokenDeleteExpiredTokensAutomaticallyInSeconds;
    private string $cacheEtagSeed;
    private int $cacheEtagUpperLimitInCollectionEndpoints;
    private bool $featureFlag280OldUniqueUserIdentifierDisabled;

    private int $fileMaxFileSizeInBytes;
    private int $fileUploadExpiresInSecondsAfterFirstRequest;
    private int $fileUploadMinChunkSizeInBytes;
    private int $fileUploadMaxChunkSizeInBytes;
    private string $fileS3StorageBucket;
    private string $fileS3UploadBucket;
    private int $fileS3StorageBucketLevels;
    private int $fileS3StorageBucketLevelLength;
    private int $fileS3UploadBucketLevels;
    private int $fileS3UploadBucketLevelLength;

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

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_MAX_FILE_SIZE_IN_BYTES,
            ]
        );
        $emberNexusConfiguration->setFileMaxFileSizeInBytes($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_UPLOAD_EXPIRES_IN_SECONDS_AFTER_FIRST_REQUEST,
            ]
        );
        $emberNexusConfiguration->setFileUploadExpiresInSecondsAfterFirstRequest($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_UPLOAD_MIN_CHUNK_SIZE_IN_BYTES,
            ]
        );
        $emberNexusConfiguration->setFileUploadMinChunkSizeInBytes($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_UPLOAD_MAX_CHUNK_SIZE_IN_BYTES,
            ]
        );
        $emberNexusConfiguration->setFileUploadMaxChunkSizeInBytes($value);

        if ($emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes() < $emberNexusConfiguration->getFileUploadMinChunkSizeInBytes()) {
            throw new Exception(sprintf('%s.%s can not be smaller than %s.%s.', self::FILE, self::FILE_MAX_FILE_SIZE_IN_BYTES, self::FILE, self::FILE_UPLOAD_MIN_CHUNK_SIZE_IN_BYTES));
        }

        $value = (string) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_STORAGE_BUCKET,
            ]
        );
        $emberNexusConfiguration->setFileS3StorageBucket($value);

        $value = (string) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_UPLOAD_BUCKET,
            ]
        );
        $emberNexusConfiguration->setFileS3UploadBucket($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_STORAGE_BUCKET_LEVELS,
            ]
        );
        $emberNexusConfiguration->setFileS3StorageBucketLevels($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_STORAGE_BUCKET_LEVEL_LENGTH,
            ]
        );
        $emberNexusConfiguration->setFileS3StorageBucketLevelLength($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_UPLOAD_BUCKET_LEVELS,
            ]
        );
        $emberNexusConfiguration->setFileS3UploadBucketLevels($value);

        $value = (int) self::getValueFromConfig(
            $configuration,
            [
                self::FILE,
                self::FILE_S3_UPLOAD_BUCKET_LEVEL_LENGTH,
            ]
        );
        $emberNexusConfiguration->setFileS3UploadBucketLevelLength($value);

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

    public function getFileMaxFileSizeInBytes(): int
    {
        return $this->fileMaxFileSizeInBytes;
    }

    public function setFileMaxFileSizeInBytes(int $fileMaxFileSizeInBytes): self
    {
        $this->fileMaxFileSizeInBytes = $fileMaxFileSizeInBytes;

        return $this;
    }

    public function getFileUploadExpiresInSecondsAfterFirstRequest(): int
    {
        return $this->fileUploadExpiresInSecondsAfterFirstRequest;
    }

    public function setFileUploadExpiresInSecondsAfterFirstRequest(int $fileUploadExpiresInSecondsAfterFirstRequest): self
    {
        $this->fileUploadExpiresInSecondsAfterFirstRequest = $fileUploadExpiresInSecondsAfterFirstRequest;

        return $this;
    }

    public function getFileUploadMinChunkSizeInBytes(): int
    {
        return $this->fileUploadMinChunkSizeInBytes;
    }

    public function setFileUploadMinChunkSizeInBytes(int $fileUploadMinChunkSizeInBytes): self
    {
        $this->fileUploadMinChunkSizeInBytes = $fileUploadMinChunkSizeInBytes;

        return $this;
    }

    public function getFileUploadMaxChunkSizeInBytes(): int
    {
        return $this->fileUploadMaxChunkSizeInBytes;
    }

    public function setFileUploadMaxChunkSizeInBytes(int $fileUploadMaxChunkSizeInBytes): self
    {
        $this->fileUploadMaxChunkSizeInBytes = $fileUploadMaxChunkSizeInBytes;

        return $this;
    }

    public function getFileS3StorageBucket(): string
    {
        return $this->fileS3StorageBucket;
    }

    public function setFileS3StorageBucket(string $fileS3StorageBucket): self
    {
        $this->fileS3StorageBucket = $fileS3StorageBucket;

        return $this;
    }

    public function getFileS3UploadBucket(): string
    {
        return $this->fileS3UploadBucket;
    }

    public function setFileS3UploadBucket(string $fileS3UploadBucket): self
    {
        $this->fileS3UploadBucket = $fileS3UploadBucket;

        return $this;
    }

    public function getFileS3StorageBucketLevels(): int
    {
        return $this->fileS3StorageBucketLevels;
    }

    public function setFileS3StorageBucketLevels(int $fileS3StorageBucketLevels): self
    {
        $this->fileS3StorageBucketLevels = $fileS3StorageBucketLevels;

        return $this;
    }

    public function getFileS3StorageBucketLevelLength(): int
    {
        return $this->fileS3StorageBucketLevelLength;
    }

    public function setFileS3StorageBucketLevelLength(int $fileS3StorageBucketLevelLength): self
    {
        $this->fileS3StorageBucketLevelLength = $fileS3StorageBucketLevelLength;

        return $this;
    }

    public function getFileS3UploadBucketLevels(): int
    {
        return $this->fileS3UploadBucketLevels;
    }

    public function setFileS3UploadBucketLevels(int $fileS3UploadBucketLevels): self
    {
        $this->fileS3UploadBucketLevels = $fileS3UploadBucketLevels;

        return $this;
    }

    public function getFileS3UploadBucketLevelLength(): int
    {
        return $this->fileS3UploadBucketLevelLength;
    }

    public function setFileS3UploadBucketLevelLength(int $fileS3UploadBucketLevelLength): self
    {
        $this->fileS3UploadBucketLevelLength = $fileS3UploadBucketLevelLength;

        return $this;
    }
}
