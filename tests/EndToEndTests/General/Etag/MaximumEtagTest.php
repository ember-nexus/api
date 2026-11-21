<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class MaximumEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:IoRXsjFDSZ658iTsGCcJlt';

    private const string PARENT_UUID = '02IiNr0EPf9nVLBEnasauVuYC2suFXEg3BrZ1';
    private const string CHILD_UUID = '02IiNr0EPf9nVLBp4mNiltySq7jyCWco86XX0';
    private const string RELATED_UUID = '02IiNr0EPf9nVLAhck4WhFbqY8Pzzzt3EoBaD';
    private const string GROUP_UUID = '02IiNr0EPf9nVLAkmzGlpQ80aghDmsWvPNFDI';
    private const string SOME_NODE_UUID = '02IiNr0EPf9nVLBFYiiJHWRfd2sSs5vfHOhaw';

    private function testEtagOfElementDoesNotExist(string $token, string $id, string $additionalPath): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $id, $additionalPath),
            $token
        );
        $this->assertCount(0, $response->getHeader('Etag'));
    }

    public function testEtagBeforeAndAfterGoingOverEtagLimit(): void
    {
        $initialEtagNodeParentSelf = $this->getEtagOfElement(self::TOKEN, self::PARENT_UUID, '', '"99FLiCULEq7"');
        $initialEtagNodeParentParents = $this->getEtagOfElement(self::TOKEN, self::PARENT_UUID, '/parents', '"aHIKEofgedd"');
        $this->getEtagOfElement(self::TOKEN, self::PARENT_UUID, '/children', '"HKEYu0A2gIb"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/related');

        $initialEtagNodeChildSelf = $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '', '"O9bbODoK7hm"');
        $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '/parents', '"6fCtl4qSJO8"');
        $initialEtagNodeChildChildren = $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '/children', '"eks3RRr0n0i"');
        $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '/related', '"6fCtl4qSJO8"');

        $initialEtagNodeRelatedSelf = $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '', '"bhlAfFejAgX"');
        $initialEtagNodeRelatedParents = $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '/parents', '"GVgaOTVBlqg"');
        $initialEtagNodeRelatedChildren = $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '/children', '"a47pUD8FNUb"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::RELATED_UUID, '/related');

        $initialEtagNodeGroupSelf = $this->getEtagOfElement(self::TOKEN, self::GROUP_UUID, '', '"Rv1u6RlJsQg"');
        $initialEtagNodeGroupParents = $this->getEtagOfElement(self::TOKEN, self::GROUP_UUID, '/parents', '"MtRQ4prX7bJ"');
        $this->getEtagOfElement(self::TOKEN, self::GROUP_UUID, '/children', '"AT8soRgRSbn"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/related');

        $initialEtagNodeSomeNodeSelf = $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '', '"G45km7RYQNi"');
        $initialEtagNodeSomeNodeParents = $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/parents', '"5sao6tY8HP1"');
        $initialEtagNodeSomeNodeChildren = $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/children', '"6EpjSXKhDJn"');
        $initialEtagNodeSomeNodeRelated = $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/related', '"CTeHcT9u0mD"');

        // add one more central node with relations to bring all etags over their limit

        $response = $this->runPostRequest(
            sprintf(
                '%s',
                self::PARENT_UUID
            ),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Data 101',
                    'scenario' => 'general.etag.maximum',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
        $dataNewCentralNode = $this->getUuidFromLocation($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'OWNS',
                'start' => $dataNewCentralNode,
                'end' => self::CHILD_UUID,
                'data' => [
                    'scenario' => 'general.etag.maximum',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'OWNS',
                'start' => self::GROUP_UUID,
                'end' => $dataNewCentralNode,
                'data' => [
                    'scenario' => 'general.etag.maximum',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATED',
                'start' => $dataNewCentralNode,
                'end' => self::RELATED_UUID,
                'data' => [
                    'scenario' => 'general.etag.maximum',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        // verify that the etags are no longer generated

        $this->getEtagOfElement(self::TOKEN, self::PARENT_UUID, '', $initialEtagNodeParentSelf);
        $this->getEtagOfElement(self::TOKEN, self::PARENT_UUID, '/parents', $initialEtagNodeParentParents);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/children');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/related');

        $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '', $initialEtagNodeChildSelf);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::CHILD_UUID, '/parents');
        $this->getEtagOfElement(self::TOKEN, self::CHILD_UUID, '/children', $initialEtagNodeChildChildren);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::CHILD_UUID, '/related');

        $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '', $initialEtagNodeRelatedSelf);
        $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '/parents', $initialEtagNodeRelatedParents);
        $this->getEtagOfElement(self::TOKEN, self::RELATED_UUID, '/children', $initialEtagNodeRelatedChildren);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::RELATED_UUID, '/related');

        $this->getEtagOfElement(self::TOKEN, self::GROUP_UUID, '', $initialEtagNodeGroupSelf);
        $this->getEtagOfElement(self::TOKEN, self::GROUP_UUID, '/parents', $initialEtagNodeGroupParents);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/children');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/related');

        $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '', $initialEtagNodeSomeNodeSelf);
        $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/parents', $initialEtagNodeSomeNodeParents);
        $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/children', $initialEtagNodeSomeNodeChildren);
        $this->getEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/related', $initialEtagNodeSomeNodeRelated);
    }
}
