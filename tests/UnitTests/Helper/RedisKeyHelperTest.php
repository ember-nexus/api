<?php

namespace App\tests\UnitTests\Helper;

use App\Helper\RedisKeyHelper;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RedisKeyHelperTest extends TestCase
{
    public function testGetTokenRedisKey(): void
    {
        $this->assertSame('', RedisKeyHelper::getTokenRedisKey());
    }

    public function testGetEtagElementRedisKey(): void
    {
        $this->assertSame(
            'etag:element:4da79017-f669-4b2a-827b-667bd49185f0',
            RedisKeyHelper::getEtagElementRedisKey(Uuid::fromString('4da79017-f669-4b2a-827b-667bd49185f0'))
        );
        $this->assertSame(
            'etag:element:c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a',
            RedisKeyHelper::getEtagElementRedisKey(Uuid::fromString('c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a'))
        );
    }

    public function testGetEtagChildrenCollectionRedisKey(): void
    {
        $this->assertSame(
            'etag:children:4da79017-f669-4b2a-827b-667bd49185f0',
            RedisKeyHelper::getEtagChildrenCollectionRedisKey(Uuid::fromString('4da79017-f669-4b2a-827b-667bd49185f0'))
        );
        $this->assertSame(
            'etag:children:c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a',
            RedisKeyHelper::getEtagChildrenCollectionRedisKey(Uuid::fromString('c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a'))
        );
    }

    public function testGetEtagParentsCollectionRedisKey(): void
    {
        $this->assertSame(
            'etag:parents:4da79017-f669-4b2a-827b-667bd49185f0',
            RedisKeyHelper::getEtagParentsCollectionRedisKey(Uuid::fromString('4da79017-f669-4b2a-827b-667bd49185f0'))
        );
        $this->assertSame(
            'etag:parents:c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a',
            RedisKeyHelper::getEtagParentsCollectionRedisKey(Uuid::fromString('c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a'))
        );
    }

    public function testGetEtagRelatedCollectionRedisKey(): void
    {
        $this->assertSame(
            'etag:related:4da79017-f669-4b2a-827b-667bd49185f0',
            RedisKeyHelper::getEtagRelatedCollectionRedisKey(Uuid::fromString('4da79017-f669-4b2a-827b-667bd49185f0'))
        );
        $this->assertSame(
            'etag:related:c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a',
            RedisKeyHelper::getEtagRelatedCollectionRedisKey(Uuid::fromString('c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a'))
        );
    }

    public function testGetEtagIndexCollectionRedisKey(): void
    {
        $this->assertSame(
            'etag:index:4da79017-f669-4b2a-827b-667bd49185f0',
            RedisKeyHelper::getEtagIndexCollectionRedisKey(Uuid::fromString('4da79017-f669-4b2a-827b-667bd49185f0'))
        );
        $this->assertSame(
            'etag:index:c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a',
            RedisKeyHelper::getEtagIndexCollectionRedisKey(Uuid::fromString('c0f040ff-dbd6-4eb1-9d4c-d29ba4ee228a'))
        );
    }
}
