<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class UpdateNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:G7dRuaI5PU7rCgYmu8gKhu';
    private const string UUID_DATA_1 = '8dd5a24c-36fa-4b91-88aa-13bde35f55f0';
    private const string UUID_DATA_2 = 'f9f126e0-c6b6-411d-8ca0-3b2263c5fc8d';
    private const string UUID_DATA_3 = 'b765aeec-9c95-4885-bc10-db221a2a0ad0';
    private const string UUID_DATA_4 = '648fc0f2-3b86-46a1-9253-4b6740fd23e1';
    private const string UUID_DATA_5 = 'b5af6e75-c77d-4275-903e-21147e198b94';
    private const string UUID_OWNS_2 = '1c6752ee-e8f0-4ce1-8881-df0054809f67';
    private const string UUID_OWNS_3 = '4ea0018f-e94b-467c-9be1-f9642d1a7555';
    private const string UUID_RELATED_1 = '3d793ff1-5587-45e3-aa17-1677dec2854a';
    private const string UUID_RELATED_2 = '5f7dfb9c-9b76-43dc-aa75-6b5a023a10e8';

    private function testEtagOfElement(string $token, string $uuid, string $additionalPath, string $shouldEtag = null): string
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

    public function testEtagBeforeAndAfterChangingCentralNode(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"32Y4YaICHSp"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"YPDrndSAaRk"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"KmKHH0KFEba"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"JIobUuqj4lb"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', '"K3bujAUVZ9Y"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', '"CWLug6b50l"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', '"J8dkcXukC1m"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', '"CQMAk9dgkDi"');

        $initialEtagNode3Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '', '"2VJvZpgLSJR"');
        $initialEtagNode3Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/parents', '"fpXUUfObYYL"');
        $initialEtagNode3Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/children', '"BJiUpVoNGZY"');
        $initialEtagNode3Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/related', '"fpXUUfObYYL"');

        $initialEtagNode4Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '', '"G42OU2B7d0V"');
        $initialEtagNode4Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/parents', '"WADj2lJWrnj"');
        $initialEtagNode4Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/children', '"Ngn7buUmo9D"');
        $initialEtagNode4Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/related', '"Kv8lDjPtkMK"');

        $initialEtagNode5Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '', '"1dMgiS1gopM"');
        $initialEtagNode5Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/parents', '"DpWQEQZWoOC"');
        $initialEtagNode5Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/children', '"eRbuG523Xua"');
        $initialEtagNode5Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/related', '"CoenIGgISgZ"');

        $initialEtagOwns2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS_2, '', '"8Yhu0Mtu4Gt"');

        $initialEtagOwns3Self = $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS_3, '', '"D2HqXBbjGHl"');

        $initialEtagRelated1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED_1, '', '"WfM9MZFrGkW"');

        $initialEtagRelated2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED_2, '', '"YASLtEtc5Jt"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::UUID_DATA_2
            ),
            self::TOKEN,
            [
                'some' => 'changed data',
            ]
        );
        $this->assertNoContentResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children');
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $finalEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '');
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', $initialEtagNode2Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', $initialEtagNode2Children);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', $initialEtagNode2Related);

        $this->assertNotSame($initialEtagNode2Self, $finalEtagNode2Self);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '', $initialEtagNode3Self);
        $finalEtagNode3Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/parents');
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/children', $initialEtagNode3Children);
        $finalEtagNode3Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/related');

        $this->assertNotSame($initialEtagNode3Parents, $finalEtagNode3Parents);
        $this->assertNotSame($initialEtagNode3Related, $finalEtagNode3Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '', $initialEtagNode4Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/parents', $initialEtagNode4Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/children', $initialEtagNode4Children);
        $finalEtagNode4Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/related');

        $this->assertNotSame($initialEtagNode4Related, $finalEtagNode4Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '', $initialEtagNode5Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/parents', $initialEtagNode5Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/children', $initialEtagNode5Children);
        $finalEtagNode5Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/related');

        $this->assertNotSame($initialEtagNode5Related, $finalEtagNode5Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS_2, '', $initialEtagOwns2Self);

        $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS_3, '', $initialEtagOwns3Self);

        $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED_1, '', $initialEtagRelated1Self);

        $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED_2, '', $initialEtagRelated2Self);
    }
}
