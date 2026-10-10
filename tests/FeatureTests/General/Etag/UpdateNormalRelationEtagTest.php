<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class UpdateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:L7mQpPOOdY2ESN6GsJIGkU';
    private const string ID_DATA_1 = '81826403-6513-40ce-a2b7-6ce1ec259df4';
    private const string ID_DATA_2 = 'eeccb6bf-91da-4da1-8bef-b797a32eb8a6';
    private const string ID_RELATED = '7f3afac6-013e-4b28-acc7-f4fe1c418c99';


    public function testEtagBeforeAndAfterUpdatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"YoB0OOEREXk"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"3j6Nn1Zg7Vh"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"CXBnJAUbSJp"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"KLXJORKMBo2"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"7VR6m1Ibrsd"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"Ii50AcImQIP"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"qgOpKWhgph"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"4MjlN2a6NE5"');

        $initialEtagRelatedSelf = $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"UoDabdDMmbq"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::ID_RELATED
            ),
            self::TOKEN,
            [
                'some' => 'changed data',
            ]
        );
        $this->assertNoContentResponse($response);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', $initialEtagNode1Children);
        $finalEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', $initialEtagNode2Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', $initialEtagNode2Parents);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);

        $finalEtagRelatedSelf = $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '');

        $this->assertNotSame($initialEtagRelatedSelf, $finalEtagRelatedSelf);
    }
}
