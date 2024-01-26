<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @group test2
 */
class MaximumEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:IoRXsjFDSZ658iTsGCcJlt';

    private const string PARENT_UUID = '806e93de-ce08-46a4-8fb0-9b8b4dd09be7';
    private const string CHILD_UUID = 'cae2ddfa-18cf-4dcb-a4dc-d758d715ae54';
    private const string RELATED_UUID = '3c47a37c-6d6b-48d8-aac0-c6bc0d0ecc94';
    private const string GROUP_UUID = '42c7a0f3-fc9b-478b-b658-52ecb44238b8';
    private const string SOME_NODE_UUID = '81f811e2-c19f-4339-94e2-c0376fec097e';

    private function getEtagFromResponse(ResponseInterface $response): string
    {
        return $response->getHeader('Etag')[0];
    }

    public function testEtagBeforeAndAfterGoingOverEtagLimit(): void
    {
        $this->markTestSkipped();
        // assert initial etag values
        $response = $this->runGetRequest(
            sprintf('/%s/children', self::PARENT_UUID),
            self::TOKEN
        );
        $initialParentEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"72kOJ5B1f1p"', $initialParentEtag);

        $response = $this->runGetRequest(
            sprintf('/%s/parents', self::CHILD_UUID),
            self::TOKEN
        );
        $initialChildEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"TA9dR0e3QFg"', $initialChildEtag);

        //        $response = $this->runGetRequest(
        //            sprintf("/%s/related", self::RELATED_UUID),
        //            self::TOKEN
        //        );
        //        $initialRelatedEtag = $this->getEtagFromResponse($response);
        //        $this->assertSame('"ITsmYNHWAVL"', $initialRelatedEtag);

        $response = $this->runGetRequest(
            sprintf('/%s/children', self::GROUP_UUID),
            self::TOKEN
        );
        $initialGroupEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"6CStc81LfoX"', $initialGroupEtag);

        // change one element -> all related element's etags will change as well

        $response = $this->runPatchRequest(
            sprintf('/%s', self::SOME_NODE_UUID),
            self::TOKEN,
            [
                'change' => '1',
            ]
        );
        $this->assertNoContentResponse($response);

        sleep(2);

        // compare that etags have changed

        $response = $this->runGetRequest(
            sprintf('/%s/children', self::PARENT_UUID),
            self::TOKEN
        );
        $changed1ParentEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"aaaa"', $changed1ParentEtag);
        $this->assertNotSame($initialParentEtag, $changed1ParentEtag);

        $response = $this->runGetRequest(
            sprintf('/%s/parents', self::CHILD_UUID),
            self::TOKEN
        );
        $changed1ChildEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"bbbb"', $changed1ChildEtag);
        $this->assertNotSame($initialChildEtag, $changed1ChildEtag);

        //        $response = $this->runGetRequest(
        //            sprintf("/%s/related", self::RELATED_UUID),
        //            self::TOKEN
        //        );
        //        $changed1RelatedEtag = $this->getEtagFromResponse($response);
        //        $this->assertSame('"cccc"', $changed1RelatedEtag);
        //        $this->assertNotSame($initialRelatedEtag, $changed1RelatedEtag);

        $response = $this->runGetRequest(
            sprintf('/%s/children', self::GROUP_UUID),
            self::TOKEN
        );
        $changed1GroupEtag = $this->getEtagFromResponse($response);
        $this->assertSame('"dddd"', $changed1GroupEtag);
        $this->assertNotSame($initialGroupEtag, $changed1GroupEtag);
    }
}
