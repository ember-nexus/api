<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies the explicit `ANONYMOUS_USER_READONLY=true` scenario, run against its own container (see
 * `api-anonymous-readonly-true` in the docker-compose files) so both the boolean-true parsing and the resulting
 * behavior are covered, independently of {@see AnonymousUserReadonlyDefaultTest}'s unset-default scenario.
 */
#[Group('anonymous-readonly-true')]
class AnonymousUserReadonlyEnabledTest extends BaseRequestTestCase
{
    public function testAnonymousUserCanStillReadWithoutToken(): void
    {
        $response = $this->runGetRequest('/me', null);
        $this->assertIsNodeResponse($response, 'User');
    }

    public function testAnonymousUserCanNotCreateElementWithoutToken(): void
    {
        $response = $this->runPostRequest('/', null, ['type' => 'Data']);
        $this->assertIsProblemResponse($response, 403);
    }

    public function testAnonymousUserCanStillSearchWithoutToken(): void
    {
        $response = $this->runPostRequest('/search', null, ['steps' => []]);
        $this->assertIsSearchResultResponse($response);
    }
}
