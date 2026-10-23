<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use App\Service\RequestContentService;
use App\Service\RequestUtilService;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
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
            new RequestContentService(new Client408RequestTimeoutExceptionFactory($urlGenerator)),
            $client400BadContentExceptionFactory,
            $client400MissingPropertyExceptionFactory,
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

    public function testGetJsonBodyFromRequest(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->assertSame(['type' => 'Test'], $requestUtilService->getJsonBodyFromRequest(new Request(content: '{"type":"Test"}')));
        $this->assertSame([], $requestUtilService->getJsonBodyFromRequest(new Request(content: '{}')));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidJsonBodyProvider(): array
    {
        return [
            'string' => ['"text"'],
            'number' => ['12'],
            'null' => ['null'],
            'list' => ['[1, 2]'],
        ];
    }

    #[DataProvider('invalidJsonBodyProvider')]
    public function testGetJsonBodyFromRequestWithNonObject(string $content): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->expectException(Client400BadContentException::class);
        $requestUtilService->getJsonBodyFromRequest(new Request(content: $content));
    }

    public function testGetArrayFromBody(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->assertSame(['a' => 1], $requestUtilService->getArrayFromBody('data', ['data' => ['a' => 1]]));

        $this->expectException(Client400BadContentException::class);
        $requestUtilService->getArrayFromBody('data', ['data' => 'text']);
    }

    public function testGetArrayFromBodyWithMissingKey(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->expectException(Client400MissingPropertyException::class);
        $requestUtilService->getArrayFromBody('data', []);
    }

    public function testGetDataFromBodyWithInvalidData(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->expectException(Client400BadContentException::class);
        $requestUtilService->getDataFromBody(['data' => 'text']);
    }

    public function testGetUuidFromBody(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $uuid = $requestUtilService->getUuidFromBody('id', ['id' => '3d5b3a5a-6a5e-4b0e-9b3c-3c1f6a2b4d5e']);
        $this->assertSame('3d5b3a5a-6a5e-4b0e-9b3c-3c1f6a2b4d5e', $uuid->toString());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidUuidBodyProvider(): array
    {
        return [
            'not a string' => [['id' => 12]],
            'not a uuid' => [['id' => 'abc']],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidUuidBodyProvider')]
    public function testGetUuidFromBodyWithInvalidValue(array $body): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->expectException(Client400BadContentException::class);
        $requestUtilService->getUuidFromBody('id', $body);
    }

    public function testGetUuidFromBodyWithMissingKey(): void
    {
        $requestUtilService = $this->getRequestUtilService();

        $this->expectException(Client400MissingPropertyException::class);
        $requestUtilService->getUuidFromBody('id', []);
    }
}
