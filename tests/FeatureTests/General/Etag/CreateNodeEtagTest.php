<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class CreateNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8FdhLaO2utuIkWG6RRlD0h';
    private const string UUID_DATA_1 = '170e9a2d-6e89-4919-8b1b-160ee30bcb3d';
    private const string UUID_DATA_3 = '43abf373-186c-42fb-afa5-4dd66e5573e2';
    private const string UUID_DATA_4 = '1ef20009-1db9-4e74-a3d4-2de7ec475dde';
    private const string UUID_DATA_5 = '47016bbb-405a-49a1-9531-8d1efa4d5e0a';

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

    public function testEtagBeforeAndAfterCreatingCentralNode(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"GJiTEF7Lrtc"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"CJesv6Ss9cb"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"1gl9B3MJpUB"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"QnDQFujTfBY"');

        $initialEtagNode3Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '', '"QalI9FT22Rr"');
        $initialEtagNode3Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/parents', '"MqvGbuM1XP5"');
        $initialEtagNode3Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/children', '"Z6jJXVf0DZH"');
        $initialEtagNode3Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_3, '/related', '"MqvGbuM1XP5"');

        $initialEtagNode4Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '', '"bBbSrlfLurR"');
        $initialEtagNode4Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/parents', '"YTAhFuoV9un"');
        $initialEtagNode4Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/children', '"PjMTcSU1inO"');
        $initialEtagNode4Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_4, '/related', '"YTAhFuoV9un"');

        $initialEtagNode5Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '', '"5VDJkfkOrSE"');
        $initialEtagNode5Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/parents', '"D9IZJiltV1G"');
        $initialEtagNode5Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/children', '"TmilseWMcEb"');
        $initialEtagNode5Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_5, '/related', '"D9IZJiltV1G"');

        $response = $this->runPostRequest(
            sprintf(
                '%s',
                self::UUID_DATA_1
            ),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Data 2',
                    'scenario' => 'general.etag.create-node',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
        $data2Uuid = $this->getUuidFromLocation($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'OWNS',
                'start' => $data2Uuid,
                'end' => self::UUID_DATA_3,
                'data' => [
                    'scenario' => 'general.etag.create-node',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATED',
                'start' => $data2Uuid,
                'end' => self::UUID_DATA_5,
                'data' => [
                    'scenario' => 'general.etag.create-node',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATED',
                'start' => self::UUID_DATA_4,
                'end' => $data2Uuid,
                'data' => [
                    'scenario' => 'general.etag.create-node',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children');
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

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
    }
}
