<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Factory\Type\RedisKeyFactory;
use App\Tests\EndToEndTests\BaseRequestTestCase;
use Predis\Client as RedisClient;
use Ramsey\Uuid\Uuid;

/**
 * Element ids are server-generated UUIDv4s and are never reused by normal API usage, so a cached Etag value can
 * never legitimately outlive the element it belongs to - except if the real cleanup on delete ever regresses (e.g.
 * a future change removes/breaks {@see \App\EventSystem\EntityManager\EventListener\ExpireEtagOnChangeEventListener}'s
 * delete-side expiry) and a client-supplied id (`POST / {"id": ...}`, also used by doc-generation fixtures) is later
 * reused for a new element. This pins both halves of the safety net: delete must clear the element's own cached
 * Etag, and even if a stale value somehow survived anyway, create must still overwrite/expire it for the reused id.
 */
class RecreatedElementIdDoesNotInheritStaleEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string POISONED_ETAG_VALUE = 'poisoned-leftover-etag';

    private function getRedis(): RedisClient
    {
        return new RedisClient($_ENV['REDIS_AUTH']);
    }

    private function elementEtagKey(string $elementId): string
    {
        return (string) (new RedisKeyFactory())->getEtagElementRedisKey(Uuid::fromString($elementId));
    }

    public function testDeleteClearsOwnCachedEtagAndRecreateDoesNotInheritAPoisonedLeftover(): void
    {
        $redis = $this->getRedis();
        $elementId = $this->createElement(self::TOKEN, 'etag-poisoning-original');
        $redisKey = $this->elementEtagKey($elementId);

        // populate the cache
        $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertSame(1, $redis->exists($redisKey));

        // deleting the element must clear its own cached Etag, not just neighbors' collection Etags (which are
        // already covered by DeleteNodeEtagTest)
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
        $this->assertSame(0, $redis->exists($redisKey));

        // simulate a leftover stale value as if a previous cleanup had failed (e.g. a crash between the delete and
        // its cache invalidation) - this can not happen with the current code, since the assertion above just
        // proved the real delete path already clears it; this reintroduces that state artificially so the second
        // half of the safety net (create) can be verified independently of the first
        $redis->set($redisKey, self::POISONED_ETAG_VALUE, 'EX', 3600);
        $this->assertSame(self::POISONED_ETAG_VALUE, $redis->get($redisKey));

        // recreate a new element with the same (now reused) id; creation must expire any pre-existing cache entry
        // for that id, so the poisoned value can never leak into a response for the new element
        $this->assertIsCreatedResponse($this->runPostRequest('/', self::TOKEN, [
            'id' => $elementId,
            'type' => 'Data',
            'data' => ['name' => 'etag-poisoning-recreated'],
        ]));
        $this->assertSame(0, $redis->exists($redisKey));

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $etag = $response->getHeader('Etag')[0];
        $this->assertNotSame(self::POISONED_ETAG_VALUE, $etag);
        $this->assertNotSame(sprintf('"%s"', self::POISONED_ETAG_VALUE), $etag);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }
}
