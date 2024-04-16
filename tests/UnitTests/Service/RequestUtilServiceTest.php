<?php

namespace App\tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Service\RequestUtilService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RequestUtilServiceTest extends TestCase
{
    private function getRequestUtilService(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory = null,
    ): RequestUtilService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);

        return new RequestUtilService(
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $client400BadContentExceptionFactory,
            $client400MissingPropertyExceptionFactory
        );
    }

    public function testValidateTypeFromBody(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
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
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got 'integer'.", $e->getDetail());
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
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'type' => 'notATest',
        ];
        try {
            $requestUtilService->validateTypeFromBody('Test', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be Test, got 'notATest'.", $e->getDetail());
        }

        $body = [
            'type' => 'Test',
        ];
        $requestUtilService->validateTypeFromBody('Test', $body);
    }

    public function testGetUniqueUserIdentifierFromDataOld(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $requestUtilService = $this->getRequestUtilService(
            emberNexusConfiguration: $emberNexusConfiguration
        );
        $method = new ReflectionMethod(RequestUtilService::class, 'getUniqueUserIdentifierFromDataOld');
        $method->setAccessible(true);

        $data = [];
        try {
            $method->invokeArgs($requestUtilService, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'data.email' to be set to string.", $e->getDetail());
        }

        $data = [
            'email' => 1234,
        ];
        try {
            $method->invokeArgs($requestUtilService, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'data.email' to be string, got 'integer'.", $e->getDetail());
        }

        $data = [
            'email' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($requestUtilService, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'data.email' to be string, got 'array'.", $e->getDetail());
        }

        $data = [
            'email' => 'test@localhost.dev',
        ];
        $uniqueUserIdentifier = $method->invokeArgs($requestUtilService, [$data]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testGetUniqueUserIdentifierFromDataNew(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $requestUtilService = $this->getRequestUtilService();
        $method = new ReflectionMethod(RequestUtilService::class, 'getUniqueUserIdentifierFromBodyNew');
        $method->setAccessible(true);

        $body = [];
        try {
            $method->invokeArgs($requestUtilService, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'uniqueUserIdentifier' to be set to string.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 1234,
        ];
        try {
            $method->invokeArgs($requestUtilService, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($requestUtilService, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 'test@localhost.dev',
        ];
        $uniqueUserIdentifier = $method->invokeArgs($requestUtilService, [$body]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testGetUniqueUserIdentifierFromBodyAndDataWithOldWayEnabled(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $emberNexusConfiguration->method('isFeatureFlag280OldUniqueUserIdentifierDisabled')->willReturn(false);
        $requestUtilService = $this->getRequestUtilService(
            emberNexusConfiguration: $emberNexusConfiguration
        );

        $body = [
            'data' => [],
        ];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'uniqueUserIdentifier' to be set to string.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 1234,
            'data' => [],
        ];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => [
                'some' => 'object',
            ],
            'data' => [],
        ];
        try {
            $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 'test@localhost.dev',
            'data' => [],
        ];
        $uniqueUserIdentifier = $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);

        $body = [
            'uniqueUserIdentifier' => 'testNew@localhost.dev',
            'data' => [
                'email' => 'testOld@localhost.dev',
            ],
        ];
        $uniqueUserIdentifier = $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
        $this->assertSame('testOld@localhost.dev', $uniqueUserIdentifier);

        $body = [
            'data' => [
                'email' => 'test@localhost.dev',
            ],
        ];
        $uniqueUserIdentifier = $requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $body['data']);
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
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $requestUtilService = $this->getRequestUtilService();

        $body = [];
        try {
            $requestUtilService->getStringFromBody('password', $body);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
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
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got 'integer'.", $e->getDetail());
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
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'password' => '1234',
        ];
        $password = $requestUtilService->getStringFromBody('password', $body);
        $this->assertSame('1234', $password);
    }
}
