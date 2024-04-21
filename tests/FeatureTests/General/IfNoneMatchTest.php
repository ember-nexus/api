<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;

class IfNoneMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:RRq4WsomBeTH0AAa7Jmi4k';
    private const string UUID_DATA = '88d75ef3-8b27-4519-af9a-baa5dc2907db';
    private const string UUID_PARENT = '17370748-35e2-41f7-ae9b-66be353b5a90';
    private const string UUID_CHILD = 'f621c1b9-1d3f-4a9c-999c-99d1edcc9c6f';
    private const string UUID_RELATED = 'b576e116-f5f1-4106-92e6-1547b8131108';

    private function testEtagOfElement(string $token, string $uuid, string $additionalPath, ?string $shouldEtag = null): string
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $uuid, $additionalPath),
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
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA, '', '"ROiR1100cKu"');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_DATA
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_DATA
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
                self::UUID_DATA
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
                self::UUID_DATA
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
                self::UUID_DATA
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
                self::UUID_DATA
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
                self::UUID_DATA
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
        $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED, '', '"IZK4tgD1OhG"');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_RELATED
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'RELATED');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_RELATED
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
                self::UUID_RELATED
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
        $this->testEtagOfElement(self::TOKEN, self::UUID_PARENT, '/children', '"d344gmYJeeQ"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::UUID_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::UUID_PARENT
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
                self::UUID_PARENT
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
        $this->testEtagOfElement(self::TOKEN, self::UUID_CHILD, '/parents', '"ZGUcWBYHppR"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::UUID_CHILD
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::UUID_CHILD
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
                self::UUID_CHILD
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
        $this->testEtagOfElement(self::TOKEN, self::UUID_PARENT, '/related', '"TVPsbpcCAeU"');

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::UUID_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response, 3, 3);

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::UUID_PARENT
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
                self::UUID_PARENT
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
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_DATA
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];
        $this->assertSame('"ROiR1100cKu"', $etag);

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::UUID_DATA
            ),
            self::TOKEN,
            [
                'new' => 'data',
            ],
            [
                'If-None-Match' => '"ROiR1100cKu"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::UUID_DATA
            ),
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
            sprintf(
                '%s',
                self::UUID_DATA
            ),
            self::TOKEN,
            [
                'new' => 'data 2',
            ],
        );
        $this->assertNoContentResponse($response);
    }

    public function testEtagIfMatchWithPutElement(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_CHILD
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];
        $this->assertSame('"QIDRBlpmjG4"', $etag);

        $response = $this->runPutRequest(
            sprintf(
                '%s',
                self::UUID_CHILD
            ),
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-None-match',
                'name' => 'Child',
            ],
            [
                'If-None-Match' => '"QIDRBlpmjG4"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runPutRequest(
            sprintf(
                '%s',
                self::UUID_CHILD
            ),
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'Child',
            ],
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runPutRequest(
            sprintf(
                '%s',
                self::UUID_CHILD
            ),
            self::TOKEN,
            [
                'new' => 'data',
                'scenario' => 'general.if-none-match',
                'name' => 'Child',
            ],
        );
        $this->assertNoContentResponse($response);
    }

    public function testEtagIfMatchWithDeleteElement(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsNodeResponse($response, 'Data');
        $etag = $response->getHeader('ETag')[0];
        $this->assertSame('"EGZLT62PpEk"', $etag);

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::UUID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"EGZLT62PpEk"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::UUID_PARENT
            ),
            self::TOKEN,
            [
                'If-None-Match' => '"wrongEtag"',
            ]
        );
        $this->assertNoContentResponse($response);
    }
}
