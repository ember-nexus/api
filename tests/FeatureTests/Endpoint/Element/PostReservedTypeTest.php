<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;

/**
 * The types User, Token and Upload are managed by the API itself and can not be created through the generic
 * element endpoints.
 */
class PostReservedTypeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * @return array<string, array{string}>
     */
    public static function reservedTypeProvider(): array
    {
        return [
            'User' => ['User'],
            'Token' => ['Token'],
            'Upload' => ['Upload'],
        ];
    }

    #[DataProvider('reservedTypeProvider')]
    public function testPostIndexWithReservedTypeReturns400(string $type): void
    {
        $id = Uuid::uuid4()->toString();
        $response = $this->runPostRequest('/', self::TOKEN, [
            'id' => $id,
            'type' => $type,
            'data' => ['name' => 'post-reserved-type-index-'.$type],
        ]);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString('reserved-type', $this->getBody($response)['type']);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s', $id), self::TOKEN), 404);
    }

    #[DataProvider('reservedTypeProvider')]
    public function testPostElementWithReservedTypeReturns400(string $type): void
    {
        $parentId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'post-reserved-type-parent-'.$type],
        ]));
        $id = Uuid::uuid4()->toString();

        $response = $this->runPostRequest(sprintf('/%s', $parentId), self::TOKEN, [
            'id' => $id,
            'type' => $type,
            'data' => ['name' => 'post-reserved-type-child-'.$type],
        ]);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString('reserved-type', $this->getBody($response)['type']);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s', $id), self::TOKEN), 404);
        $childrenResponse = $this->runGetRequest(sprintf('/%s/children', $parentId), self::TOKEN);
        $this->assertSame(0, $this->getBody($childrenResponse)['totalNodes']);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $parentId), self::TOKEN));
    }

    #[DataProvider('reservedTypeProvider')]
    public function testPostIndexRelationWithReservedTypeReturns400(string $type): void
    {
        $startId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'post-reserved-type-relation-start-'.$type],
        ]));
        $endId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'post-reserved-type-relation-end-'.$type],
        ]));

        $response = $this->runPostRequest('/', self::TOKEN, [
            'type' => $type,
            'start' => $startId,
            'end' => $endId,
        ]);
        $this->assertIsProblemResponse($response, 400);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $startId), self::TOKEN));
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $endId), self::TOKEN));
    }
}
