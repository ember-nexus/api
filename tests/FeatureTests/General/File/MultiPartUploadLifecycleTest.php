<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class MultiPartUploadLifecycleTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 43038489;
    private const int FILE_SIZE = 23 * 1024 * 1024;

    public function testMultiPartUploadLifecycle(): void
    {
        // create big file to be uploaded
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, __DIR__.'/../../Asset/23MB.txt');

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

        // create new resumable upload ---------------------------------------------------------------------------------
        //        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        //        $postFileResponse = $this->runUploadRequest(
        //            'POST',
        //            sprintf('/%s/file', $elementId),
        //            $file,
        //            self::TOKEN,
        //            [
        //                'Content-Disposition' => 'inline; filename=cherry-blossoms.jpg',
        //                'Content-Type' => 'image/jpg',
        //            ]
        //        );
        //        $this->assertIsCreatedResponse($postFileResponse, false);
    }
}
