<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class CreateNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8FdhLaO2utuIkWG6RRlD0h';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLAPU9pMrrOL0XLALi41Q3yH1';
    private const string ID_DATA_3 = '02IiNr0EPf9nVLAlDR8ZqyP0fodgaPwiMVtaZ';
    private const string ID_DATA_4 = '02IiNr0EPf9nVLATLi5sh4UANSFqRY3o17BL5';
    private const string ID_DATA_5 = '02IiNr0EPf9nVLAmrFuWfgTKrDkIcV8Sr88QP';

    public function testEtagBeforeAndAfterCreatingCentralNode(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"6dXsmTXseBO"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"ZhB4DK6Pfgb"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"aVcsUTfq2bX"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"BbDFX5cNMhv"');

        $initialEtagNode3Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '', '"L9WkboEYt5c"');
        $initialEtagNode3Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents', '"KnbMP30Kecs"');
        $initialEtagNode3Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', '"Wv8P7vBJKtK"');
        $initialEtagNode3Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related', '"KnbMP30Kecs"');

        $initialEtagNode4Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '', '"ac2KoSUWTnW"');
        $initialEtagNode4Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', '"8Y5EWm8IDBA"');
        $initialEtagNode4Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', '"7Xq0HIaOSoS"');
        $initialEtagNode4Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related', '"8Y5EWm8IDBA"');

        $initialEtagNode5Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '', '"8jLiDsKekuv"');
        $initialEtagNode5Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', '"IeQM9vlVQMn"');
        $initialEtagNode5Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', '"Eapl4hi3QB5"');
        $initialEtagNode5Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related', '"IeQM9vlVQMn"');

        $response = $this->runPostRequest(
            sprintf(
                '%s',
                self::ID_DATA_1
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
                'end' => self::ID_DATA_3,
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
                'end' => self::ID_DATA_5,
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
                'start' => self::ID_DATA_4,
                'end' => $data2Uuid,
                'data' => [
                    'scenario' => 'general.etag.create-node',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children');
        $finalEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '', $initialEtagNode3Self);
        $finalEtagNode3Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', $initialEtagNode3Children);
        $finalEtagNode3Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related');

        $this->assertNotSame($initialEtagNode3Parents, $finalEtagNode3Parents);
        $this->assertNotSame($initialEtagNode3Related, $finalEtagNode3Related);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '', $initialEtagNode4Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', $initialEtagNode4Parents);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', $initialEtagNode4Children);
        $finalEtagNode4Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related');

        $this->assertNotSame($initialEtagNode4Related, $finalEtagNode4Related);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '', $initialEtagNode5Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', $initialEtagNode5Parents);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', $initialEtagNode5Children);
        $finalEtagNode5Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related');

        $this->assertNotSame($initialEtagNode5Related, $finalEtagNode5Related);
    }
}
