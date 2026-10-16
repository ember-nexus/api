<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies the explicit `ANONYMOUS_USER_READONLY=false` scenario, run against its own container (see
 * `api-anonymous-readonly-false` in the docker-compose files): with the restriction disabled, the anonymous user
 * regains full write access, matching the behavior from before this restriction existed.
 */
#[Group('anonymous-readonly-false')]
class AnonymousUserReadonlyDisabledTest extends BaseRequestTestCase
{
    public function testAnonymousUserCanStillReadWithoutToken(): void
    {
        $response = $this->runGetRequest('/me', null);
        $this->assertIsNodeResponse($response, 'User');
    }

    public function testAnonymousUserCanCreateElementWithoutToken(): void
    {
        $response = $this->runPostRequest('/', null, ['type' => 'Data']);
        $this->assertIsCreatedResponse($response);

        $elementId = $this->getUuidFromLocation($response);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), null));
    }

    public function testAnonymousUserCanStillSearchWithoutToken(): void
    {
        $response = $this->runPostRequest('/search', null, ['steps' => []]);
        $this->assertIsSearchResultResponse($response);
    }
}
