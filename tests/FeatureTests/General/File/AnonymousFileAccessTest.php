<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unauthenticated requests are treated as the fixed anonymous user (ANONYMOUS_USER_UUID). The
 * "general.anonymousUser" reference dataset scenario seeds a Data element owned by that user, with a file
 * attached, so that anonymous file access can be verified without any Authorization header.
 */
#[Group('test')]
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
