<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Type\TusIoExtensionType;
use App\Type\TusIoHeaderType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @param array<string, mixed> $body
     * @param array<string, mixed> $data
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    protected function getUniqueUserIdentifierFromBodyAndDataOld(array $body, array $data): ?string
    {
        if (array_key_exists('user', $body)) {
            $uniqueUserIdentifier = $body['user'];
            if (is_string($uniqueUserIdentifier)) {
                return $uniqueUserIdentifier;
            }
        }
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
                $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromBodyAndDataOld($body, $data);
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
    public function getStringFromBody(string $key, array $body): string
    {
        if (!array_key_exists($key, $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate($key, 'string');
        }
        $value = $body[$key];
        if (!is_string($value)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($key, 'string', gettype($value));
        }

        return $value;
    }

    public function validateTusResumableHttpHeader(Request $request): void
    {
        // The Tus-Resumable header MUST be included in every request and response except for OPTIONS requests.
        if (Request::METHOD_OPTIONS === $request->getMethod()) {
            return;
        }
        if ($request->headers->has(TusIoHeaderType::TUS_RESUMABLE->value)) {
        }
    }

    public function setCommonTusHeaders(Response $response): void
    {
        $response->headers->set(TusIoHeaderType::TUS_VERSION->value, '1.0.0');
        $response->headers->set(TusIoHeaderType::TUS_RESUMABLE->value, '1.0.0');

        $supportedExtensions = [
            TusIoExtensionType::CREATION_WITH_UPLOAD->value,
            TusIoExtensionType::CREATION_DEFER_LENGTH->value,
            TusIoExtensionType::EXPIRATION->value,
            TusIoExtensionType::CHECKSUM->value,
            TusIoExtensionType::TERMINATION->value,
        ];
        $response->headers->set(TusIoHeaderType::TUS_EXTENSION->value, implode(',', $supportedExtensions));

        // 5 TB, max limit of S3
        $response->headers->set(TusIoHeaderType::TUS_MAX_SIZE->value, (string) (5 * 1024 * 1024 * 1024 * 1024));
    }
}
