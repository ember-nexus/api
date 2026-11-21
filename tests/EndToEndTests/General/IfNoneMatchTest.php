<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class IfNoneMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:RRq4WsomBeTH0AAa7Jmi4k';
    private const string ID_DATA = '02IiNr0EPf9nVLBIuHYAwqBZoaz85PTYYstCi';
    private const string ID_PARENT = '02IiNr0EPf9nVLAPZlLLN9h0MVh8ly8VrFCb8';
    private const string ID_CHILD = '02IiNr0EPf9nVLCA8YZ3annMXymmQYJJ0uq5q';
    private const string ID_RELATED = '02IiNr0EPf9nVLBedZqZn38dBHiUSvac95XDo';

    public function testIfMatchElementNode(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA, '', '"N6jR7Fg9FeT"');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"N6jR7Fg9FeT"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchDifferentHeaderKeyCasing(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'if-none-match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'if-none-match' => '"N6jR7Fg9FeT"',
            ]
        );
        $this->assertNotModifiedResponse($response);
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'IF-NONE-MATCH' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'IF-NONE-MATCH' => '"N6jR7Fg9FeT"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchElementRelation(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"EKcfGpREuY9"');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_RELATED
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'RELATED');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_RELATED
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsNodeResponse($response, 'RELATED');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_RELATED
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"EKcfGpREuY9"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchIndex(): void
    {
        $this->getEtagOfElement(self::TOKEN, '', '', '"HFhSDVce4mn"');

        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            [
                'If-None-Match' => '"HFhSDVce4mn"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchChildren(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/children', '"JQ6dFMu0Vif"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::ID_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::ID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::ID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"JQ6dFMu0Vif"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchParents(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_CHILD, '/parents', '"9MCGoVpJu5O"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::ID_CHILD
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::ID_CHILD
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::ID_CHILD
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"9MCGoVpJu5O"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchRelated(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/related', '"dFDqv56JcRc"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::ID_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 3, 3);

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::ID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsCollectionResponse($response, 3, 3);

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::ID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"dFDqv56JcRc"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testEtagIfMatchWithPatchElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-patch-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runPatchRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
            ],
            [
                'If-None-Match' => $etag,
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runPatchRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
            ],
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runPatchRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data 2',
            ],
        );
        $this->assertNoContentResponse($response);

        $this->assertIsDeletedResponse($this->runDeleteRequest($elementId, self::TOKEN));
    }

    public function testEtagIfMatchWithPutElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-put-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'if-none-match-put-element',
            ],
            [
                'If-None-Match' => $etag,
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'if-none-match-put-element',
            ],
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'if-none-match-put-element',
            ],
        );
        $this->assertNoContentResponse($response);

        $this->assertIsDeletedResponse($this->runDeleteRequest($elementId, self::TOKEN));
    }

    public function testEtagIfMatchWithDeleteElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-delete-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runDeleteRequest(
            $elementId,
            self::TOKEN,
            [
                'If-None-Match' => $etag,
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runDeleteRequest(
            $elementId,
            self::TOKEN,
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);
    }
}
