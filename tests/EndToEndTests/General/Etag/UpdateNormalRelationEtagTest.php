<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class UpdateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:L7mQpPOOdY2ESN6GsJIGkU';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLBFLTAj2dmGxTFCTuumIsx2t';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLC6XyTeXcoDq03gW67mo4BJD';
    private const string ID_RELATED = '02IiNr0EPf9nVLBEDiNgbQvPp11A1wwJrlTXL';

    public function testEtagBeforeAndAfterUpdatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"g2W8L0ZJ2tg"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"NmMMSmZuvIZ"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"AHpVrN9GurL"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"aAvG3moNAHC"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"68ZX4mtDuOn"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"STcEmbKIqmN"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"bCVEBGfRPBT"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"Arco5fCsuia"');

        $initialEtagRelatedSelf = $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"1qbHJ4K4s6I"');

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
