<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class IfMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:M3WHIDj4q62EY0XiZFMLnv';
    private const string ID_DATA = '02IiNr0EPf9nVLAeSXQS6b16IfFg9aNoX8Vj4';
    private const string ID_PARENT = '02IiNr0EPf9nVLC3rw4ikBk2gP56rk5cMuybf';
    private const string ID_CHILD = '02IiNr0EPf9nVLBanob1kPi9qxPOHHZJ8DSti';
    private const string ID_RELATED = '02IiNr0EPf9nVLAP7Sl6Xm9YO2PLTt87DnJqL';

    public function testIfMatchElementNode(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA, '', '"Ab5tfHLLWiQ"');

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
                'If-Match' => '"Ab5tfHLLWiQ"',
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
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
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
                'if-match' => '"Ab5tfHLLWiQ"',
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
                'if-match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::ID_DATA
            ),
            self::TOKEN,
            [
                'IF-MATCH' => '"Ab5tfHLLWiQ"',
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
                'IF-MATCH' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchElementRelation(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"LgsupQ0JF0c"');

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
                'If-Match' => '"LgsupQ0JF0c"',
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
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchIndex(): void
    {
        $this->getEtagOfElement(self::TOKEN, '', '', '"KXF7jSeciBq"');

        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            [
                'If-Match' => '"KXF7jSeciBq"',
            ]
        );
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchChildren(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/children', '"OCmYgMeR5G3"');

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
                'If-Match' => '"OCmYgMeR5G3"',
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
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchParents(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_CHILD, '/parents', '"8psbSBqOAFS"');

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
                'If-Match' => '"8psbSBqOAFS"',
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
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchRelated(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/related', '"YCsIRJiNDhn"');

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
                'If-Match' => '"YCsIRJiNDhn"',
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
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testEtagIfMatchWithPatchElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-match-patch-element'],
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
                'If-Match' => '"wrongEtag"',
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
                'If-Match' => $etag,
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
            'data' => ['name' => 'if-match-put-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-match',
                'name' => 'if-match-put-element',
            ],
            [
                'If-Match' => '"wrongEtag"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-match',
                'name' => 'if-match-put-element',
            ],
            [
                'If-Match' => $etag,
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runPutRequest(
            $elementId,
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-match',
                'name' => 'if-match-put-element',
            ],
        );
        $this->assertNoContentResponse($response);

        $this->assertIsDeletedResponse($this->runDeleteRequest($elementId, self::TOKEN));
    }

    public function testEtagIfMatchWithDeleteElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-match-delete-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runDeleteRequest(
            $elementId,
            self::TOKEN,
            [
                'If-Match' => '"wrongEtag"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runDeleteRequest(
            $elementId,
            self::TOKEN,
            [
                'If-Match' => $etag,
            ]
        );
        $this->assertNoContentResponse($response);
    }
}
