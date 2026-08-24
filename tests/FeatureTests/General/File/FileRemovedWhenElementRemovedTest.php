<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that a file is no longer accessible once the element it was attached to is deleted, even though
 * the file itself was never explicitly deleted through the file endpoint.
 */
#[Group('test')]
class FileRemovedWhenElementRemovedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testFileIsGoneAfterElementIsDeleted(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-removed-when-element-removed',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $getFileResponse1 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($getFileResponse1, 'application/octet-stream');

        $deleteElementResponse = $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteElementResponse);

        $getElementResponse = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getElementResponse, 404);

        $getFileResponse2 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getFileResponse2, 404);
    }
}
