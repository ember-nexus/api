<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Exception\InvalidUuidException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Type\Uuid;
use Symfony\Component\HttpFoundation\Request;

class RequestUtilService
{
    public function __construct(
        private RequestContentService $requestContentService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Client400BadContentException
     */
    public function getJsonBodyFromRequest(Request $request): array
    {
        $body = \Safe\json_decode($this->requestContentService->getContent($request), true);
        if (!is_array($body) || (count($body) > 0 && array_is_list($body))) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Unable to parse request as JSON object, got %s.', get_debug_type($body)));
        }

        /** @var array<string, mixed> $body */
        return $body;
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
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'string', $type);
        }
        if ($expectedType !== $type) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', $expectedType, $body['type']);
        }
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function getUniqueUserIdentifierFromBodyAndData(array $body): string
    {
        if (!array_key_exists('uniqueUserIdentifier', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string');
        }
        $uniqueUserIdentifier = $body['uniqueUserIdentifier'];
        if (!is_string($uniqueUserIdentifier)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string', $uniqueUserIdentifier);
        }

        return $uniqueUserIdentifier;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     *
     * @throws Client400BadContentException
     */
    public function getDataFromBody(array $body): array
    {
        if (!array_key_exists('data', $body)) {
            return [];
        }

        return $this->getArrayFromBody('data', $body);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function getArrayFromBody(string $key, array $body): array
    {
        if (!array_key_exists($key, $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate($key, 'object');
        }
        $value = $body[$key];
        if (!is_array($value)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($key, 'object', $value);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    public function getUuidFromBody(string $key, array $body): Uuid
    {
        $value = $this->getStringFromBody($key, $body);
        try {
            return Uuid::fromString($value);
        } catch (InvalidUuidException $exception) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($key, 'uuid', $value, $exception);
        }
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
            throw $this->client400BadContentExceptionFactory->createFromTemplate($key, 'string', $value);
        }

        return $value;
    }
}
