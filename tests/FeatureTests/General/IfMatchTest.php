<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;

class IfMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:M3WHIDj4q62EY0XiZFMLnv';
    private const string ID_DATA = '35cd3b18-0d0c-4e98-876e-898b930797f2';
    private const string ID_PARENT = 'e94ebb96-8cca-49eb-a214-ba73a72abba0';
    private const string ID_CHILD = 'ad966733-6cfb-427b-8661-8207a58bdc7f';
    private const string ID_RELATED = '1647af8f-2f6a-46de-ab8a-3f1a740761f3';


    public function testIfMatchElementNode(): void
    {
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA, '', '"6JM8JahrCeu"');

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
                'If-Match' => '"6JM8JahrCeu"',
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
                'if-match' => '"6JM8JahrCeu"',
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
                'IF-MATCH' => '"6JM8JahrCeu"',
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
        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"fMmIm5Rb9kp"');

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
                'If-Match' => '"fMmIm5Rb9kp"',
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
        $this->getEtagOfElement(self::TOKEN, '', '', '"ZWkfjHF1QO1"');

        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            [
                'If-Match' => '"ZWkfjHF1QO1"',
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
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/children', '"V3s5O8medDn"');

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
                'If-Match' => '"V3s5O8medDn"',
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
        $this->getEtagOfElement(self::TOKEN, self::ID_CHILD, '/parents', '"If6HLZuIreW"');

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
                'If-Match' => '"If6HLZuIreW"',
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
        $this->getEtagOfElement(self::TOKEN, self::ID_PARENT, '/related', '"5DkIZtvdg3q"');

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
                'If-Match' => '"5DkIZtvdg3q"',
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
