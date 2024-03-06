<?php

namespace App\tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;

class IfMatchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:M3WHIDj4q62EY0XiZFMLnv';
    private const string UUID_DATA = '35cd3b18-0d0c-4e98-876e-898b930797f2';
    private const string UUID_PARENT = 'e94ebb96-8cca-49eb-a214-ba73a72abba0';
    private const string UUID_CHILD = 'ad966733-6cfb-427b-8661-8207a58bdc7f';
    private const string UUID_RELATED = '1647af8f-2f6a-46de-ab8a-3f1a740761f3';

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
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA, '', '"6JM8JahrCeu"');

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
            headers: [
                'If-Match' => '"6JM8JahrCeu"',
            ]
        );
        $this->assertIsNodeResponse($response, 'Data');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_DATA
            ),
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchElementRelation(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED, '', '"fMmIm5Rb9kp"');

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
            headers: [
                'If-Match' => '"fMmIm5Rb9kp"',
            ]
        );
        $this->assertIsNodeResponse($response, 'RELATED');

        $response = $this->runGetRequest(
            sprintf(
                '%s',
                self::UUID_RELATED
            ),
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchIndex(): void
    {
        $this->testEtagOfElement(self::TOKEN, '', '', '"ZWkfjHF1QO1"');

        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            headers: [
                'If-Match' => '"ZWkfjHF1QO1"',
            ]
        );
        $this->assertIsCollectionResponse($response, 2, 0);

        $response = $this->runGetRequest(
            '/',
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchChildren(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::UUID_PARENT, '/children', '"V3s5O8medDn"');

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
            headers: [
                'If-Match' => '"V3s5O8medDn"',
            ]
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/children',
                self::UUID_PARENT
            ),
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchParents(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::UUID_CHILD, '/parents', '"If6HLZuIreW"');

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
            headers: [
                'If-Match' => '"If6HLZuIreW"',
            ]
        );
        $this->assertIsCollectionResponse($response, 1, 1);

        $response = $this->runGetRequest(
            sprintf(
                '%s/parents',
                self::UUID_CHILD
            ),
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }

    public function testIfMatchRelated(): void
    {
        $this->testEtagOfElement(self::TOKEN, self::UUID_PARENT, '/related', '"5DkIZtvdg3q"');

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
            headers: [
                'If-Match' => '"5DkIZtvdg3q"',
            ]
        );
        $this->assertIsCollectionResponse($response, 3, 3);

        $response = $this->runGetRequest(
            sprintf(
                '%s/related',
                self::UUID_CHILD
            ),
            self::TOKEN,
            headers: [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
    }
}
