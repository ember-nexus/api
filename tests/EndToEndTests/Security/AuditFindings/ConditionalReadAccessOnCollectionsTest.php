<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * `HAS_READ_ACCESS` can be limited with `onLabel`, `onParentLabel`, `onState` and `onCreatedByUser`. The condition has to
 * hold for the index (`GET /`) as well, not only for `GET /{id}`: an element the user may not read must neither be
 * listed nor hydrated.
 */
class ConditionalReadAccessOnCollectionsTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    /**
     * @return string[]
     */
    private function indexIds(string $token): array
    {
        $ids = [];
        $page = 1;
        do {
            $response = $this->runGetRequest('/?page='.$page, $token);
            $this->assertSame(200, $response->getStatusCode());
            $body = $this->getBody($response);
            $ids = [...$ids, ...array_column($body['nodes'], 'id')];
            ++$page;
        } while (null !== $body['links']['next'] && $page < 20);

        return $ids;
    }

    private const string USER_TOKEN = 'secret-token:8BXna8kGtTSwhsgU3JwwUS';

    /**
     * Elements of the reference dataset scenario `security.conditionalIndex`: the user has a `HAS_READ_ACCESS` relation
     * with a condition (or none) to each of them.
     *
     * @return iterable<string, array{string, bool}> element id, whether the user may read it
     */
    public static function elements(): iterable
    {
        yield 'unconditional' => ['02IiNr0GOset2DC1055evM27FDfnxbTg07MNA', true];
        yield 'onLabel matching' => ['02IiNr0GOset2DB6EnkzbU6k0cKYgleQkX4Bm', true];
        yield 'onLabel not matching' => ['02IiNr0GOset2DAwm26Ct5FVYzgNLmE0Mmw4P', false];
        yield 'onParentLabel matching' => ['02IiNr0GOset2DAVzPyk0UOkgjFAgMDN3iuh6', true];
        yield 'onParentLabel not matching' => ['02IiNr0GOset2DBPAUYiNjbErttJGmalBJZ7H', false];
        yield 'onCreatedByUser matching' => ['02IiNr0GOset2DBQmVVGsFJK1mSwruQmd7AG0', true];
        yield 'onCreatedByUser not matching' => ['02IiNr0GOset2Dhnox9Olb5LdXCj8HfPNim2w', false];
        yield 'onState matching' => ['02IiNr0GOset2DhZIsDAA7xJ7Abr2gImeTEor', true];
        yield 'onState not matching' => ['02IiNr0GOset2DhodypyB3pUDtDpeqT4Mkhvx', false];
    }

    #[DataProvider('elements')]
    public function testIndexOnlyListsElementsWhichSatisfyTheCondition(string $elementId, bool $mayRead): void
    {
        $this->assertSame($mayRead ? 200 : 404, $this->runGetRequest('/'.$elementId, self::USER_TOKEN)->getStatusCode(), 'GET /{id}');

        if ($mayRead) {
            $this->assertContains($elementId, $this->indexIds(self::USER_TOKEN));
        } else {
            $this->assertNotContains($elementId, $this->indexIds(self::USER_TOKEN), 'GET / lists an element the condition does not cover');
        }
    }

    public function testIndexListsExactlyTheReadableElements(): void
    {
        $listed = array_filter(
            array_map(static fn (array $case): ?string => $case[1] ? $case[0] : null, iterator_to_array(self::elements(), false))
        );
        $notListed = array_filter(
            array_map(static fn (array $case): ?string => $case[1] ? null : $case[0], iterator_to_array(self::elements(), false))
        );

        $ids = $this->indexIds(self::USER_TOKEN);

        $this->assertEmpty(array_diff($listed, $ids));
        $this->assertEmpty(array_intersect($notListed, $ids));
    }

    public function testUnconditionalAccessIsListed(): void
    {
        $owner = $this->registerUser('conditional-owner');
        $user = $this->registerUser('conditional-user');
        $element = $this->createNodeAs($owner['token']);
        $this->grantInGraph('HAS_READ_ACCESS', $user['id'], $element);

        $this->assertContains($element, $this->indexIds($user['token']));
    }

    public function testOwnedAndGroupElementsAreStillListed(): void
    {
        $user = $this->registerUser('conditional-user');
        $owned = $this->createNodeAs($user['token']);
        $group = $this->createNodeAs($user['token'], 'Group', 'my group');

        $ids = $this->indexIds($user['token']);

        $this->assertContains($owned, $ids);
        $this->assertContains($group, $ids);
    }

    public function testElementWithTwoRelationsIsListedIfOneOfThemApplies(): void
    {
        $owner = $this->registerUser('conditional-owner');
        $user = $this->registerUser('conditional-user');
        $element = $this->createNodeAs($owner['token']);
        $this->grantInGraph('HAS_READ_ACCESS', $user['id'], $element, ['onLabel' => 'Secret']);
        $this->assertNotContains($element, $this->indexIds($user['token']));

        $this->grantInGraph('HAS_READ_ACCESS', $user['id'], $element);

        $this->assertContains($element, $this->indexIds($user['token']));
    }
}
