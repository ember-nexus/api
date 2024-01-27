<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class MaximumEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:IoRXsjFDSZ658iTsGCcJlt';

    private const string PARENT_UUID = '806e93de-ce08-46a4-8fb0-9b8b4dd09be7';
    private const string CHILD_UUID = 'cae2ddfa-18cf-4dcb-a4dc-d758d715ae54';
    private const string RELATED_UUID = '3c47a37c-6d6b-48d8-aac0-c6bc0d0ecc94';
    private const string GROUP_UUID = '42c7a0f3-fc9b-478b-b658-52ecb44238b8';
    private const string SOME_NODE_UUID = '81f811e2-c19f-4339-94e2-c0376fec097e';

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

    private function testEtagOfElementDoesNotExist(string $token, string $uuid, string $additionalPath): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $uuid, $additionalPath),
            $token
        );
        $this->assertCount(0, $response->getHeader('Etag'));
    }

    public function testEtagBeforeAndAfterGoingOverEtagLimit(): void
    {
        $initialEtagNodeParentSelf = $this->testEtagOfElement(self::TOKEN, self::PARENT_UUID, '', '"XIoWpD03PDi"');
        $initialEtagNodeParentParents = $this->testEtagOfElement(self::TOKEN, self::PARENT_UUID, '/parents', '"CH5uJMBOiL5"');
        $this->testEtagOfElement(self::TOKEN, self::PARENT_UUID, '/children', '"72kOJ5B1f1p"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/related');

        $initialEtagNodeChildSelf = $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '', '"MF9LXcsbS9W"');
        $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '/parents', '"TA9dR0e3QFg"');
        $initialEtagNodeChildChildren = $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '/children', '"YumWkClVdjC"');
        $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '/related', '"TA9dR0e3QFg"');

        $initialEtagNodeRelatedSelf = $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '', '"ITsmYNHWAVL"');
        $initialEtagNodeRelatedParents = $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '/parents', '"A14DEvTRqFm"');
        $initialEtagNodeRelatedChildren = $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '/children', '"elPrb0N3odH"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::RELATED_UUID, '/related');

        $initialEtagNodeGroupSelf = $this->testEtagOfElement(self::TOKEN, self::GROUP_UUID, '', '"ghh3kF8BXvi"');
        $initialEtagNodeGroupParents = $this->testEtagOfElement(self::TOKEN, self::GROUP_UUID, '/parents', '"gfRnoZmYUIs"');
        $this->testEtagOfElement(self::TOKEN, self::GROUP_UUID, '/children', '"6CStc81LfoX"');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/related');

        $initialEtagNodeSomeNodeSelf = $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '', '"X3ETsa7nPQg"');
        $initialEtagNodeSomeNodeParents = $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/parents', '"PNXGItBje4l"');
        $initialEtagNodeSomeNodeChildren = $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/children', '"EfU099ffuDv"');
        $initialEtagNodeSomeNodeRelated = $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/related', '"QpKhKWN0FsH"');

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

        // todo: enable test once https://github.com/ember-nexus/api/issues/238 is fixed
        // $response = $this->runPostRequest(
        //     '/',
        //     self::TOKEN,
        //     [
        //         'type' => 'OWNS',
        //         'start' => self::GROUP_UUID,
        //         'end' => $dataNewCentralNode,
        //         'data' => [
        //             'scenario' => 'general.etag.maximum',
        //         ],
        //     ]
        // );
        // $this->assertIsCreatedResponse($response);

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

        $this->testEtagOfElement(self::TOKEN, self::PARENT_UUID, '', $initialEtagNodeParentSelf);
        $this->testEtagOfElement(self::TOKEN, self::PARENT_UUID, '/parents', $initialEtagNodeParentParents);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/children');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::PARENT_UUID, '/related');

        $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '', $initialEtagNodeChildSelf);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::CHILD_UUID, '/parents');
        $this->testEtagOfElement(self::TOKEN, self::CHILD_UUID, '/children', $initialEtagNodeChildChildren);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::CHILD_UUID, '/related');

        $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '', $initialEtagNodeRelatedSelf);
        $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '/parents', $initialEtagNodeRelatedParents);
        $this->testEtagOfElement(self::TOKEN, self::RELATED_UUID, '/children', $initialEtagNodeRelatedChildren);
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::RELATED_UUID, '/related');

        $this->testEtagOfElement(self::TOKEN, self::GROUP_UUID, '', $initialEtagNodeGroupSelf);
        $this->testEtagOfElement(self::TOKEN, self::GROUP_UUID, '/parents', $initialEtagNodeGroupParents);
        // todo: enable test once https://github.com/ember-nexus/api/issues/238 is fixed
        // $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/children');
        $this->testEtagOfElementDoesNotExist(self::TOKEN, self::GROUP_UUID, '/related');

        $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '', $initialEtagNodeSomeNodeSelf);
        $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/parents', $initialEtagNodeSomeNodeParents);
        $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/children', $initialEtagNodeSomeNodeChildren);
        $this->testEtagOfElement(self::TOKEN, self::SOME_NODE_UUID, '/related', $initialEtagNodeSomeNodeRelated);
    }
}
