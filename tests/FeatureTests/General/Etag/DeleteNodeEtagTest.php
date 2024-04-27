<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:Tn2aRIttthKXKaAqUXjId';
    private const string ID_DATA_1 = '55765f93-0373-48a7-89d1-4cb203827204';
    private const string ID_DATA_2 = '407d6936-c871-4cb5-bd70-faa2c426ea1b';
    private const string ID_DATA_3 = 'fb2b4efb-6a36-4681-92b1-65e593809c6b';
    private const string ID_DATA_4 = '4b04fac0-a2db-454c-9d1d-1880a82ecd0f';
    private const string ID_DATA_5 = '02a52492-ee1d-43fe-ad1a-bde040552fd0';

    private function testEtagOfElement(string $token, string $id, string $additionalPath, ?string $shouldEtag = null): string
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $id, $additionalPath),
            $token
        );
        $etag = $response->getHeader('Etag')[0];
        if ($shouldEtag) {
            $this->assertSame($shouldEtag, $etag);
        }

        return $etag;
    }

    public function testEtagBeforeAndAfterDeletingCentralNode(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"SPPl9HgNJoQ"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"9egU8GlmZhd"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"XovCNrKbfle"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"FZ6aqOLaiW5"');

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"YpnfkN4NVqq"');
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"PQM1gARCGid"');
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"KNH6fLhOk5J"');
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"UD1sPlHH5nt"');

        $initialEtagNode3Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '', '"VnrkHEG5DdH"');
        $initialEtagNode3Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents', '"VoESTGuVvEJ"');
        $initialEtagNode3Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', '"4PhJYUg8ISZ"');
        $initialEtagNode3Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related', '"VoESTGuVvEJ"');

        $initialEtagNode4Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '', '"TkVt26Dobi3"');
        $initialEtagNode4Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', '"U5rj9sPDp03"');
        $initialEtagNode4Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', '"ULOTh08uBbH"');
        $initialEtagNode4Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related', '"La7aCFnFhrJ"');

        $initialEtagNode5Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '', '"S07I2LP5XV8"');
        $initialEtagNode5Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', '"UgIIjmuu5Qi"');
        $initialEtagNode5Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', '"UPX8l4eSUGT"');
        $initialEtagNode5Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related', '"6uEpSIAul6O"');

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::ID_DATA_2
            ),
            self::TOKEN
        );
        $this->assertIsDeletedResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children');
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '', $initialEtagNode3Self);
        $finalEtagNode3Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents');
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', $initialEtagNode3Children);
        $finalEtagNode3Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related');

        $this->assertNotSame($initialEtagNode3Parents, $finalEtagNode3Parents);
        $this->assertNotSame($initialEtagNode3Related, $finalEtagNode3Related);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '', $initialEtagNode4Self);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', $initialEtagNode4Parents);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', $initialEtagNode4Children);
        $finalEtagNode4Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related');

        $this->assertNotSame($initialEtagNode4Related, $finalEtagNode4Related);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '', $initialEtagNode5Self);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', $initialEtagNode5Parents);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', $initialEtagNode5Children);
        $finalEtagNode5Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related');

        $this->assertNotSame($initialEtagNode5Related, $finalEtagNode5Related);
    }
}
