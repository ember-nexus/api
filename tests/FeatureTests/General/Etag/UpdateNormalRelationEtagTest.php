<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class UpdateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:L7mQpPOOdY2ESN6GsJIGkU';
    private const string UUID_DATA_1 = '81826403-6513-40ce-a2b7-6ce1ec259df4';
    private const string UUID_DATA_2 = 'eeccb6bf-91da-4da1-8bef-b797a32eb8a6';
    private const string UUID_RELATED = '7f3afac6-013e-4b28-acc7-f4fe1c418c99';

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

    public function testEtagBeforeAndAfterUpdatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"YoB0OOEREXk"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"3j6Nn1Zg7Vh"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"CXBnJAUbSJp"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"KLXJORKMBo2"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', '"7VR6m1Ibrsd"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', '"Ii50AcImQIP"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', '"qgOpKWhgph"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', '"4MjlN2a6NE5"');

        $initialEtagRelatedSelf = $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED, '', '"UoDabdDMmbq"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::UUID_RELATED
            ),
            self::TOKEN,
            [
                'some' => 'changed data',
            ]
        );
        $this->assertNoContentResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', $initialEtagNode1Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', $initialEtagNode1Children);
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', $initialEtagNode2Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', $initialEtagNode2Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);

        $finalEtagRelatedSelf = $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED, '');

        $this->assertNotSame($initialEtagRelatedSelf, $finalEtagRelatedSelf);
    }
}
