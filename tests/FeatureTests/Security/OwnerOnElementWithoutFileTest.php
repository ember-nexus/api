<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies how the file endpoints behave for the owner of an element which has no file. Successful file operations
 * of owners are covered by {@see FileEndpointAccessControlTest}.
 */
class OwnerOnElementWithoutFileTest extends BaseRequestTestCase
{
    private const string TOKEN_OWNER = 'secret-token:V8m72O3ovtRU09JrdbJRnh';

    private string $elementId;

    protected function setUp(): void
    {
        parent::setUp();
        $response = $this->runPostRequest(
            '/',
            self::TOKEN_OWNER,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'owner-on-element-without-file-test',
                ],
            ]
        );
        $this->elementId = $this->getUuidFromLocation($response);
    }

    protected function tearDown(): void
    {
        $this->runDeleteRequest(sprintf('/%s', $this->elementId), self::TOKEN_OWNER);
        parent::tearDown();
    }

    public function testOwnerGetsNotFoundWhenRequestingFileOfElementWithoutFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', $this->elementId), self::TOKEN_OWNER);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testOwnerGetsNotFoundWhenDeletingFileOfElementWithoutFile(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', $this->elementId), self::TOKEN_OWNER);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testOwnerGetsMethodNotAllowedWhenPatchingFileEndpoint(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', $this->elementId), self::TOKEN_OWNER, []);
        $this->assertIsProblemResponse($response, 405);
    }
}
