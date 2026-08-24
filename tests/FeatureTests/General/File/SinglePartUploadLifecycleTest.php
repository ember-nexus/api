<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class SinglePartUploadLifecycleTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSinglePartUploadLifecycle(): void
    {
        // create new node for file upload
        $postNodeResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $elementId = substr($postNodeResponse->getHeader('Location')[0], 1);

        // verify that node does not have file property
        $getNodeResponse1 = $this->runGetRequest(
            sprintf('/%s', $elementId),
            self::TOKEN
        );
        $getNodeResponseData1 = json_decode((string) $getNodeResponse1->getBody(), true);
        $this->assertArrayNotHasKey('file', $getNodeResponseData1);

        // verify that accessing file results in 404 error
        $getFileResponse1 = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsProblemResponse($getFileResponse1, 404);

        // upload file -------------------------------------------------------------------------------------------------
        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Disposition' => 'inline; filename=cherry-blossoms.jpg',
                'Content-Type' => 'image/jpg',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        // verify that node now does have file property, and etag is different
        $getNodeResponse2 = $this->runGetRequest(
            sprintf('/%s', $elementId),
            self::TOKEN
        );
        $getNodeResponseData2 = json_decode((string) $getNodeResponse2->getBody(), true);
        $this->assertNotSame($getNodeResponse1->getHeader('ETag'), $getNodeResponse2->getHeader('ETag'));
        $this->assertArrayHasKey('file', $getNodeResponseData2);
        $this->assertSame(
            [
                'contentLength' => 63933,
                'extension' => 'jpg',
                'mimeType' => 'image/jpeg',
                'hash' => [
                    'sha256' => hash_file('sha256', __DIR__.'/../../Asset/cherry-blossoms.jpg'),
                ],
            ],
            $getNodeResponseData2['file']
        );

        // verify that file can be downloaded
        $getFileResponse2 = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsBinaryStreamResponse($getFileResponse2, 'application/octet-stream');

        // delete file -------------------------------------------------------------------------------------------------
        $deleteFileResponse = $this->runDeleteRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsDeletedResponse($deleteFileResponse);

        // verify that accessing file results in 404 error
        $getFileResponse3 = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsProblemResponse($getFileResponse3, 404);

        // verify that node does not have file property
        $getNodeResponse3 = $this->runGetRequest(
            sprintf('/%s', $elementId),
            self::TOKEN
        );
        $getNodeResponseData3 = json_decode((string) $getNodeResponse3->getBody(), true);
        $this->assertArrayNotHasKey('file', $getNodeResponseData3);
        $this->assertNotSame($getNodeResponse1->getHeader('ETag'), $getNodeResponse3->getHeader('ETag'));
        $this->assertNotSame($getNodeResponse2->getHeader('ETag'), $getNodeResponse3->getHeader('ETag'));
    }
}
