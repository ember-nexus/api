<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;

class IfNoneMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:RRq4WsomBeTH0AAa7Jmi4k';
    private const string ID_DATA = '88d75ef3-8b27-4519-af9a-baa5dc2907db';
    private const string ID_PARENT = '17370748-35e2-41f7-ae9b-66be353b5a90';
    private const string ID_CHILD = 'f621c1b9-1d3f-4a9c-999c-99d1edcc9c6f';
    private const string ID_RELATED = 'b576e116-f5f1-4106-92e6-1547b8131108';
    // dedicated write-capable token for tests below which mutate/delete their own elements: TOKEN's fixed reference
    // dataset ids above are also read by WildcardEtagTest (same "if-none-match" scenario fixtures), and paratest does
    // not guarantee that class runs after the mutations here have happened
    private const string WRITE_TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function testEtagOfElement(string $token, string $id, string $additionalPath, ?string $shouldEtag = null): string
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $id, $additionalPath),
            $token
        );
        $etag = $response->getHeader('Etag')[0];
        if ($shouldEtag) {
            $this->assertSame($shouldEtag, $etag);
        }

        return $etag;
    }

    public function testIfMatchElementNode(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA, '', '"ROiR1100cKu"');

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
                'If-None-Match' => '"ROiR1100cKu"',
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
                'if-none-match' => '"ROiR1100cKu"',
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
                'IF-NONE-MATCH' => '"ROiR1100cKu"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchElementRelation(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"IZK4tgD1OhG"');

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
                'If-None-Match' => '"IZK4tgD1OhG"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchIndex(): void
    {
        $this->testEtagOfElement(self::TOKEN, '', '', '"VFHTCT94KoT"');

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
                'If-None-Match' => '"VFHTCT94KoT"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchChildren(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::ID_PARENT, '/children', '"d344gmYJeeQ"');

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
                'If-None-Match' => '"d344gmYJeeQ"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchParents(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::ID_CHILD, '/parents', '"ZGUcWBYHppR"');

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
                'If-None-Match' => '"ZGUcWBYHppR"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testIfMatchRelated(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::ID_PARENT, '/related', '"TVPsbpcCAeU"');

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
                'If-None-Match' => '"TVPsbpcCAeU"',
            ]
        );
        $this->assertNotModifiedResponse($response);
    }

    public function testEtagIfMatchWithPatchElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::WRITE_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-patch-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::WRITE_TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runPatchRequest(
            $elementId,
            self::WRITE_TOKEN,
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
            self::WRITE_TOKEN,
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
            self::WRITE_TOKEN,
            [
                'new' => 'data 2',
            ],
        );
        $this->assertNoContentResponse($response);

        $this->assertIsDeletedResponse($this->runDeleteRequest($elementId, self::WRITE_TOKEN));
    }

    public function testEtagIfMatchWithPutElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::WRITE_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-put-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::WRITE_TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runPutRequest(
            $elementId,
            self::WRITE_TOKEN,
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
            self::WRITE_TOKEN,
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
            self::WRITE_TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'if-none-match-put-element',
            ],
        );
        $this->assertNoContentResponse($response);

        $this->assertIsDeletedResponse($this->runDeleteRequest($elementId, self::WRITE_TOKEN));
    }

    public function testEtagIfMatchWithDeleteElement(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::WRITE_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'if-none-match-delete-element'],
        ]));

        $response = $this->runGetRequest($elementId, self::WRITE_TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];

        $response = $this->runDeleteRequest(
            $elementId,
            self::WRITE_TOKEN,
            [
                'If-None-Match' => $etag,
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runDeleteRequest(
            $elementId,
            self::WRITE_TOKEN,
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);
    }
}
