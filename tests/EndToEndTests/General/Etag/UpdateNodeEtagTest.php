<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class UpdateNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:G7dRuaI5PU7rCgYmu8gKhu';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLBLLE8u3hfN4ldFMgIzXkqJz';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLCBzeotp25MNRw5UsvX7Q8xR';
    private const string ID_DATA_3 = '02IiNr0EPf9nVLBfa43wUGFgeakQRFLt3BO4I';
    private const string ID_DATA_4 = '02IiNr0EPf9nVLB1E0q5jurVsXbhfaiLl8Vig';
    private const string ID_DATA_5 = '02IiNr0EPf9nVLBejIjT79bUCYlIbFNN3rDXV';
    private const string ID_OWNS_2 = '02IiNr0EPf9nVLAS6NqEPe5vCT3A4sm8jFhaW';
    private const string ID_OWNS_3 = '02IiNr0EPf9nVLAqZSKP0gOyYT0Xmt6QTwq7v';
    private const string ID_RELATED_1 = '02IiNr0EPf9nVLAiCbf8dMoQhamVc2jK4SNP5';
    private const string ID_RELATED_2 = '02IiNr0EPf9nVLAyl9eRzCU2HkGVHQ9S81ub4';

    public function testEtagBeforeAndAfterUpdatingCentralNode(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"8u9W2v2BgB8"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"MvqOvV0K7L5"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"LZ3vRtcFRCb"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"VWWPZLAThio"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"WN10lZ84TTa"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"WDfLiUcBuIo"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"6NqQFB8IrS9"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"FLD5MCkMIvA"');

        $initialEtagNode3Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '', '"YqDFOBEcKf4"');
        $initialEtagNode3Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents', '"etgFA33oWQo"');
        $initialEtagNode3Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', '"aseBISCltZD"');
        $initialEtagNode3Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related', '"etgFA33oWQo"');

        $initialEtagNode4Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '', '"UvQK9Cn9KEG"');
        $initialEtagNode4Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', '"VKN4tYiQnUT"');
        $initialEtagNode4Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', '"8fssMap8HCr"');
        $initialEtagNode4Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related', '"81PmoRpXJJO"');

        $initialEtagNode5Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '', '"uHPN3SkM5T"');
        $initialEtagNode5Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', '"dGLFtjSWHuh"');
        $initialEtagNode5Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', '"L7nZTo1XY5G"');
        $initialEtagNode5Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related', '"QBgIj35YL8m"');

        $initialEtagOwns2Self = $this->getEtagOfElement(self::TOKEN, self::ID_OWNS_2, '', '"43CJKtaHkJs"');

        $initialEtagOwns3Self = $this->getEtagOfElement(self::TOKEN, self::ID_OWNS_3, '', '"Wq3ENVvW3MA"');

        $initialEtagRelated1Self = $this->getEtagOfElement(self::TOKEN, self::ID_RELATED_1, '', '"EI8D8Ku4D8N"');

        $initialEtagRelated2Self = $this->getEtagOfElement(self::TOKEN, self::ID_RELATED_2, '', '"LGg7PcC7EZL"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::ID_DATA_2
            ),
            self::TOKEN,
            [
                'some' => 'changed data',
            ]
        );
        $this->assertNoContentResponse($response);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children');
        $finalEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $finalEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', $initialEtagNode2Parents);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', $initialEtagNode2Children);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', $initialEtagNode2Related);

        $this->assertNotSame($initialEtagNode2Self, $finalEtagNode2Self);

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

        $this->getEtagOfElement(self::TOKEN, self::ID_OWNS_2, '', $initialEtagOwns2Self);

        $this->getEtagOfElement(self::TOKEN, self::ID_OWNS_3, '', $initialEtagOwns3Self);

        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED_1, '', $initialEtagRelated1Self);

        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED_2, '', $initialEtagRelated2Self);
    }
}
