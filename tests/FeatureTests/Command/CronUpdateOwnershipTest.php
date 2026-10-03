<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\Attributes\Group;

/**
 * Executes `cron:update-ownership`, which recalculates `_groupsWithSearchAccess`/`_usersWithSearchAccess` for
 * elements affected by `OWNS`/`HAS_SEARCH_ACCESS`/`IS_IN_GROUP` relation changes.
 */
#[Group('command')]
class CronUpdateOwnershipTest extends BaseCronTestCase
{
    private const string QUEUE = 'ELASTICSEARCH_UPDATE_OWNERSHIP';
    private const string DEAD_LETTER_QUEUE = 'ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter';
    private const int QUEUE_MAX_LENGTH = 100_000;

    private function declareQueues(): void
    {
        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        // same arguments as QueueService uses to declare this (durable, bounded, dead-lettered) queue
        $channel->queue_declare(self::DEAD_LETTER_QUEUE, false, true, false, false, false, new AMQPTable([
            'x-max-length' => self::QUEUE_MAX_LENGTH,
        ]));
        $channel->queue_declare(self::QUEUE, false, true, false, false, false, new AMQPTable([
            'x-max-length' => self::QUEUE_MAX_LENGTH,
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => self::DEAD_LETTER_QUEUE,
        ]));
        $channel->close();
        $connection->close();
    }

    private function getQueueMessageCount(): int
    {
        $this->declareQueues();

        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        [, $messageCount] = $channel->queue_declare(self::QUEUE, true);
        $channel->close();
        $connection->close();

        return (int) $messageCount;
    }

    private function publishRawMessage(string $body): void
    {
        $this->declareQueues();

        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        $channel->basic_publish(new AMQPMessage($body), '', self::QUEUE);
        $channel->close();
        $connection->close();
    }

    private function drainQueue(): void
    {
        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        // messages which fail are requeued, so poison messages of other tests are removed by their third try
        for ($try = 0; $try < 3 && $this->getQueueMessageCount() > 0; ++$try) {
            $this->runConsoleCommand('cron:update-ownership');
        }
        $this->assertSame(0, $this->getQueueMessageCount());
    }

    /**
     * Registers a fresh user and returns [userId, token], independent of any shared/reference fixtures.
     *
     * @return array{0: string, 1: string}
     */
    private function registerUserWithToken(string $identifierPrefix): array
    {
        $email = sprintf('%s-%s@cron-update-ownership.localhost.dev', $identifierPrefix, bin2hex(random_bytes(6)));
        $password = '1234';

        $registerResponse = $this->runPostRequest('/register', null, [
            'type' => 'User',
            'password' => $password,
            'uniqueUserIdentifier' => $email,
        ]);
        $this->assertIsCreatedResponse($registerResponse);
        $userId = $this->getUuidFromLocation($registerResponse);

        $tokenResponse = $this->runPostRequest('/token', null, [
            'type' => 'Token',
            'uniqueUserIdentifier' => $email,
            'password' => $password,
        ]);
        $this->assertSame(201, $tokenResponse->getStatusCode());
        $token = \Safe\json_decode((string) $tokenResponse->getBody(), true)['token'];

        return [$userId, $token];
    }

    /**
     * Resolves the user id behind a token, via its Token node's `OWNS`/`CREATED` relation (its start is the owning
     * user; a self-registered user's token has `CREATED`, a backup-loaded fixture token has `OWNS`).
     */
    private function getUserIdForToken(string $token): string
    {
        $tokenResponse = $this->runGetRequest('/token', $token);
        $this->assertIsNodeResponse($tokenResponse, 'Token');
        $tokenId = $this->getBody($tokenResponse)['id'];

        $relatedResponse = $this->runGetRequest(sprintf('/%s/related', $tokenId), $token);
        $this->assertIsCollectionResponse($relatedResponse);
        $relatedData = $this->getBody($relatedResponse);
        foreach ($relatedData['relations'] as $relation) {
            if (in_array($relation['type'], ['OWNS', 'CREATED'], true)) {
                return $relation['start'];
            }
        }
        $this->fail(sprintf('Unable to determine the user id for token via %s.', $tokenId));
    }

    /**
     * Creating a relation via the HTTP endpoint requires CREATE access on its start and READ access on its end
     * (`PostIndexController`), so an independent, freshly registered user does not otherwise have any relationship
     * to self::TOKEN's user or to elements it owns. This grants self::TOKEN's user direct `OWNS` access to the given
     * (otherwise unrelated) user directly in the graph, as a *test precondition only* - it deliberately bypasses the
     * application (no event is dispatched for it), the same way other feature tests poke Cypher/Mongo directly to
     * set up a precondition (see e.g. BaseCronTestCase::setUploadProperty()). The relation actually under test in
     * each scenario below is always created afterwards through the real HTTP endpoint, so it does exercise the
     * event listener/queue/command end to end.
     */
    private function grantAdminOwnershipOf(string $userId): void
    {
        $adminUserId = $this->getUserIdForToken(self::TOKEN);
        $result = $this->getCypherClient()->run(
            'MATCH (admin:User {id: $adminUserId}), (target:User {id: $userId}) '.
            'CREATE (admin)-[r:OWNS {id: randomUUID(), created: datetime(), updated: datetime()}]->(target) '.
            'RETURN r.id AS id',
            ['adminUserId' => $adminUserId, 'userId' => $userId]
        );
        $this->assertNotNull($result->first()->get('id'));
    }

    /**
     * Creating a relation requires CREATE access on its start and READ access on its end; defaults to self::TOKEN,
     * which has that for anything it (directly or transitively, via grantAdminOwnershipOf()) owns.
     */
    private function createRelation(string $type, string $startId, string $endId, ?string $token = null): string
    {
        $response = $this->runPostRequest('/', $token ?? self::TOKEN, [
            'type' => $type,
            'start' => $startId,
            'end' => $endId,
        ]);
        $this->assertIsCreatedResponse($response);

        return $this->getUuidFromLocation($response);
    }

    /**
     * Search access is only eventually consistent with the graph (it is only recalculated once
     * `cron:update-ownership` drains the queue), and Elasticsearch itself only refreshes near-real-time, so this
     * polls briefly rather than asserting on the very first attempt.
     */
    private function assertSearchAccess(string $token, string $elementId, bool $expectedToBeFound): void
    {
        $found = false;
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $response = $this->runPostRequest('/search', $token, [
                'steps' => [
                    [
                        'type' => 'elasticsearch-query-dsl-mixin',
                        'query' => [
                            'ids' => [
                                'values' => [$elementId],
                            ],
                        ],
                        'parameters' => [
                            'nodeTypes' => ['Data'],
                        ],
                    ],
                ],
            ]);
            $this->assertIsSearchResultResponse($response);
            $data = $this->getBody($response);
            $found = false;
            foreach ($data['results']['elements'] as $result) {
                if ($result['id'] === $elementId) {
                    $found = true;
                    break;
                }
            }
            if ($found === $expectedToBeFound) {
                break;
            }
            usleep(300_000);
        }
        $this->assertSame($expectedToBeFound, $found, sprintf(
            'Expected element %s to %sbe found via search for the given token.',
            $elementId,
            $expectedToBeFound ? '' : 'not '
        ));
    }

    public function testNewOwnsEdgeGrantsSearchAccessAndItsDeletionRevokesItAgain(): void
    {
        $this->drainQueue();

        [$newUserId, $newUserToken] = $this->registerUserWithToken('owns');
        $this->grantAdminOwnershipOf($newUserId);
        $elementId = $this->createNode('cron-update-ownership-owns-target');

        $this->assertSearchAccess($newUserToken, $elementId, false);

        $relationId = $this->createRelation('OWNS', $newUserId, $elementId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertMatchesRegularExpression('/updated search access of [1-9]\d* element\(s\)/', $output);
        $this->assertSame(0, $this->getQueueMessageCount());

        $this->assertSearchAccess($newUserToken, $elementId, true);

        // running it again with no further relation changes recomputes the same values (delta zero)
        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('updated search access of 0 element(s)', $output);

        // delete the underlying OWNS relation: the node loses search access for that user again
        $this->deleteNode($relationId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertMatchesRegularExpression('/updated search access of [1-9]\d* element\(s\)/', $output);

        $this->assertSearchAccess($newUserToken, $elementId, false);

        $this->deleteNode($elementId);
        $this->drainQueue();
        $this->deleteNode($newUserId);
        $this->drainQueue();
    }

    /**
     * A pure `OWNS`-owned-by-the-group scenario, as this test's name would suggest, is not actually how this
     * application's access model behaves for *direct* per-user access: `AccessChecker::getDirectUsersWithAccessToNode()`
     * only honors a group hop for a *user's own* `_usersWithSearchAccess` entry when the querying user also created
     * the element themselves (see the existing `_99_01_IsInGroupAfterOwnsHaveNoEffectTest`, which documents that a
     * plain `IS_IN_GROUP` + `OWNS` chain grants no *direct, per-user* access this way). That is not how group access
     * to search actually works though: `_groupsWithSearchAccess` is calculated (and matched at query time against
     * the searching user's own groups) independently of any of that - see
     * testGroupMemberGainsSearchAccessViaGroupHasSearchAccessWithoutOwningOrCreatingTheElement() below, which covers
     * that actual outcome. This test therefore only covers the `IS_IN_GROUP` relation-change mechanism itself (queued
     * and drained without error), not a search-access outcome.
     */
    public function testGroupMembershipChangeIsQueuedAndConsumedWithoutError(): void
    {
        $this->drainQueue();

        [$memberUserId] = $this->registerUserWithToken('group-member');
        $this->grantAdminOwnershipOf($memberUserId);

        $groupNodeId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Group',
            'data' => ['name' => 'cron-update-ownership-group'],
        ]));

        $this->createRelation('IS_IN_GROUP', $memberUserId, $groupNodeId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(0, $this->getQueueMessageCount());

        $this->deleteNode($groupNodeId);
        $this->deleteNode($memberUserId);
        $this->drainQueue();
    }

    /**
     * Covers the actual outcome of group-conferred search access: `AccessChecker::getDirectGroupsWithAccessToNode()`
     * (unlike its per-user sibling `getDirectUsersWithAccessToNode()`) does *not* require the querying user to have
     * created the element - a group's `HAS_SEARCH_ACCESS` relation is resolved once into `_groupsWithSearchAccess`,
     * and `ElasticsearchQueryDslMixinSearchStepEventListener::buildCombinedQuery()` matches that field at query time
     * against whichever groups the searching user is currently in (`AccessChecker::getUsersGroups()`). So a group
     * member gains search access to everything the group has `HAS_SEARCH_ACCESS` to, even without ever having
     * created or directly owned any of it themselves - unlike the (deliberately unresolved) direct, per-user
     * `_usersWithSearchAccess` case covered by testGroupMembershipChangeIsQueuedAndConsumedWithoutError() above.
     */
    public function testGroupMemberGainsSearchAccessViaGroupHasSearchAccessWithoutOwningOrCreatingTheElement(): void
    {
        $this->drainQueue();

        [$memberUserId, $memberUserToken] = $this->registerUserWithToken('group-search-access-member');
        $this->grantAdminOwnershipOf($memberUserId);

        // created by the admin, not by the member: the member has no OWNS/CREATED relation to it whatsoever
        $elementId = $this->createNode('cron-update-ownership-group-search-access-target');
        $this->assertSearchAccess($memberUserToken, $elementId, false);

        $groupNodeId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Group',
            'data' => ['name' => 'cron-update-ownership-group-search-access-group'],
        ]));
        $this->createRelation('IS_IN_GROUP', $memberUserId, $groupNodeId);
        $relationId = $this->createRelation('HAS_SEARCH_ACCESS', $groupNodeId, $elementId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(0, $this->getQueueMessageCount());

        $this->assertSearchAccess($memberUserToken, $elementId, true);

        // revoking the group's HAS_SEARCH_ACCESS relation removes the member's search access again
        $this->deleteNode($relationId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);

        $this->assertSearchAccess($memberUserToken, $elementId, false);

        $this->deleteNode($groupNodeId);
        $this->deleteNode($elementId);
        $this->drainQueue();
        $this->deleteNode($memberUserId);
        $this->drainQueue();
    }

    public function testDisabledCronLeavesTheQueueUntouched(): void
    {
        $this->drainQueue();
        [$newUserId] = $this->registerUserWithToken('disabled');
        $this->grantAdminOwnershipOf($newUserId);
        $elementId = $this->createNode('cron-update-ownership-disabled');
        $this->createRelation('OWNS', $newUserId, $elementId);
        $messageCount = $this->getQueueMessageCount();
        $this->assertGreaterThanOrEqual(1, $messageCount);

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership', ['DISABLE_CRON' => '1']);

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Cron is disabled', $output);
        $this->assertSame($messageCount, $this->getQueueMessageCount());

        $this->deleteNode($elementId);
        $this->deleteNode($newUserId);
        $this->drainQueue();
    }

    public function testUnprocessableMessageIsRequeuedTwiceAndDroppedOnTheThirdTry(): void
    {
        $this->drainQueue();
        // valid JSON, but without any of the ids the command looks for
        $this->publishRawMessage('{"unexpected":"message"}');
        $this->assertSame(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(0, $this->getQueueMessageCount());
    }

    public function testMalformedJsonMessageDoesNotBlockValidMessages(): void
    {
        $this->drainQueue();
        $this->publishRawMessage('this is not json');
        [$newUserId] = $this->registerUserWithToken('malformed');
        $this->grantAdminOwnershipOf($newUserId);
        $elementId = $this->createNode('cron-update-ownership-after-malformed-message');
        $this->createRelation('OWNS', $newUserId, $elementId);

        [$exitCode, $output] = $this->runConsoleCommand('cron:update-ownership');

        $this->assertSame(0, $exitCode, $output);
        $this->assertMatchesRegularExpression('/updated search access of [1-9]\d* element\(s\)/', $output);
        // the malformed message is still waiting for its remaining tries
        $this->assertSame(1, $this->getQueueMessageCount());

        $this->deleteNode($elementId);
        $this->deleteNode($newUserId);
        $this->drainQueue();
    }
}
