<?php

namespace App\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;

class RequestUtilService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function validateTypeFromBody(string $expectedType, array $body): void
    {
        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'string');
        }
        $type = $body['type'];
        if (!is_string($type)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'string', gettype($type));
        }
        if ($expectedType !== $type) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', $expectedType, $body['type']);
        }
    }

    /**
     * @deprecated will be removed in version 0.2.0
     * @see GitHub issue #280
     *
     * @param array<string, mixed> $data
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    protected function getUniqueUserIdentifierFromDataOld(array $data): string
    {
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        if (!array_key_exists($uniqueIdentifier, $data)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate(sprintf('data.%s', $uniqueIdentifier), 'string');
        }
        $uniqueUserIdentifier = $data[$uniqueIdentifier];
        if (!is_string($uniqueUserIdentifier)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('data.%s', $uniqueIdentifier), 'string', gettype($uniqueUserIdentifier));
        }

        return $uniqueUserIdentifier;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    protected function getUniqueUserIdentifierFromBodyNew(array $body): string
    {
        if (!array_key_exists('uniqueUserIdentifier', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string');
        }
        $uniqueUserIdentifier = $body['uniqueUserIdentifier'];
        if (!is_string($uniqueUserIdentifier)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string', gettype($uniqueUserIdentifier));
        }

        return $uniqueUserIdentifier;
    }

    /**
     * Function's content will be replaced by the content of function self::getUniqueUserIdentifierFromBodyNew with the
     * release of version 0.2.0.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $data
     *
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function getUniqueUserIdentifierFromBodyAndData(array $body, array $data): string
    {
        $uniqueUserIdentifier = null;
        if (!$this->emberNexusConfiguration->isFeatureFlag280OldUniqueUserIdentifierDisabled()) {
            try {
                /**
                 * @psalm-suppress DeprecatedMethod
                 */
                $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromDataOld($data);
            } catch (Exception $e) {
            }
        }
        if (null === $uniqueUserIdentifier) {
            $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromBodyNew($body);
        }

        return $uniqueUserIdentifier;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    public function getDataFromBody(array $body): array
    {
        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function getPasswordFromBody(array $body): string
    {
        if (!array_key_exists('password', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('password', 'string');
        }
        $password = $body['password'];
        if (!is_string($password)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('password', 'string', gettype($password));
        }

        return $body['password'];
    }
}
