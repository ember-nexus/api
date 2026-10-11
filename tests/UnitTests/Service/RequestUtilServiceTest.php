<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Service\RequestUtilService;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(RequestUtilService::class)]
#[AllowMockObjectsWithoutExpectations]
class RequestUtilServiceTest extends TestCase
{
    private function getRequestUtilService(
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory = null,
    ): RequestUtilService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);

        return new RequestUtilService(
            $client400BadContentExceptionFactory,
            $client400MissingPropertyExceptionFactory
        );
    }

    public function testValidateTypeFromBody(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var Client400MissingPropertyException $e
             */
            $this->assertSame("Endpoint requires that the request contains property 'type' to be set to string.", $e->getDetail());
        }

        $body = [
            'type' => 1234,
        ];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got int 1234.", $e->getDetail());
        }

        $body = [
            'type' => [
                'some' => 'object',
            ],
        ];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got array with one element.", $e->getDetail());
        }

        $body = [
            'type' => 'notATest',
        ];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'type' to be Test, got string 'notATest'.", $e->getDetail());
        }

        $body = [
            'type' => 'Test',
        ];
        $requestUtilService->validateTypeFromBody('Test', $body);
    }

    public function testGetUniqueUserIdentifierFromBodyAndData(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var Client400MissingPropertyException $e
             */
            $this->assertSame("Endpoint requires that the request contains property 'uniqueUserIdentifier' to be set to string.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 1234,
        ];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got int 1234.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => [
                'some' => 'object',
            ],
        ];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got array with one element.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 'test@localhost.dev',
        ];
        $uniqueUserIdentifier = $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testGetDataFromBody(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        $data = $requestUtilService->getDataFromBody($body);
        $this->assertEmpty($data);

        $body = [
            'someOtherKey' => 'test',
        ];
        $data = $requestUtilService->getDataFromBody($body);
        $this->assertEmpty($data);

        $body = [
            'data' => [],
        ];
        $data = $requestUtilService->getDataFromBody($body);
        $this->assertEmpty($data);

        $body = [
            'data' => [
                'key' => 'value',
            ],
        ];
        $data = $requestUtilService->getDataFromBody($body);
        $this->assertArrayHasKey('key', $data);
    }

    public function testGetStringFromBody(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        try {
            $requestUtilService->getStringFromBody('password', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var Client400MissingPropertyException $e
             */
            $this->assertSame("Endpoint requires that the request contains property 'password' to be set to string.", $e->getDetail());
        }

        $body = [
            'password' => 1234,
        ];
        try {
            $requestUtilService->getStringFromBody('password', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got int 1234.", $e->getDetail());
        }

        $body = [
            'password' => [
                'some' => 'object',
            ],
        ];
        try {
            $requestUtilService->getStringFromBody('password', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got array with one element.", $e->getDetail());
        }

        $body = [
            'password' => '1234',
        ];
        $password = $requestUtilService->getStringFromBody('password', $body);
        $this->assertSame('1234', $password);
    }
}
