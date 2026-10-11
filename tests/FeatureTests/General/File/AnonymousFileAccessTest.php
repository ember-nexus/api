<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that unauthenticated requests (treated as the anonymous user) can access a file owned by the
 * anonymous user, seeded by the "general.anonymousUser" reference dataset scenario.
 */
class AnonymousFileAccessTest extends BaseRequestTestCase
{
    private const string ANONYMOUS_DATA_ID = '9edb178e-1b4a-4518-a9d3-0fa97e6d1007';

    public function testAnonymousUserCanAccessOwnedElementAndFile(): void
    {
        $elementResponse = $this->runGetRequest(sprintf('/%s', self::ANONYMOUS_DATA_ID), null);
        $this->assertIsNodeResponse($elementResponse, 'Data');

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', self::ANONYMOUS_DATA_ID), null);
        $this->assertIsBinaryStreamResponse($fileResponse, 'image/jpeg');
    }
}
