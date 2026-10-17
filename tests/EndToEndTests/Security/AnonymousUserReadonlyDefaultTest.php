<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * Verifies the default behavior of `ANONYMOUS_USER_READONLY` (unset, defaulting to `true`): anonymous requests
 * stay read-only, except for the handful of endpoints that do not require authentication by design.
 *
 * @see AnonymousUserReadonlyEnabledTest for the explicit `true` scenario (same behavior, different container)
 * @see AnonymousUserReadonlyDisabledTest for the explicit `false` scenario
 */
class AnonymousUserReadonlyDefaultTest extends BaseRequestTestCase
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
