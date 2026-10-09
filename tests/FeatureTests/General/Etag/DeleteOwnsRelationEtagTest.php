<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteOwnsRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:83ArPQkCKE5bSPiF3bhLmv';
    private const string ID_DATA_1 = 'd82c07a7-5b49-489e-889c-e59351b84797';
    private const string ID_DATA_2 = '8b0e08f1-beda-4753-91ba-26f2c2546cdb';
    private const string ID_OWNS = '8adecb8f-01ca-41c9-907c-dca9ee8f4bc9';

    public function testEtagBeforeAndAfterDeletingCentralOwnsRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"3fKNknCFOBH"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"9F1JTjQKoVJ"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"Dq278kamNMf"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"Jl2uY8TavUB"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"BDPMr0qWeQd"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"WTg34RDiXJb"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"SRimvnbGFLT"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"WTg34RDiXJb"');

        $this->getEtagOfElement(self::TOKEN, self::ID_OWNS, '', '"efJI5bhHtUD"');

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::ID_OWNS
            ),
            self::TOKEN
        );
        $this->assertNoContentResponse($response);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children');
        $finalEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', $initialEtagNode2Self);
        $finalEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Parents, $finalEtagNode2Parents);
        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);
    }
}
