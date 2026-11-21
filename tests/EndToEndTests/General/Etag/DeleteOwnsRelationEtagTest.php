<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class DeleteOwnsRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:83ArPQkCKE5bSPiF3bhLmv';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLBvWswUE9M07NYBZJVyASsF3';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLBJyNKLPvqmt1WEqC7nvmKS2';
    private const string ID_OWNS = '02IiNr0EPf9nVLBJsihJNQgYE1CFMgxGv3DBl';

    public function testEtagBeforeAndAfterDeletingCentralOwnsRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"8mBTpJZeqCp"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"4k4m1G6r5lX"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"Nj2YRqqBS07"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"Zn483VEPulV"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"YWONTmeN5o9"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"ZEXWickgYP2"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"RXZnPShJ5VP"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"ZEXWickgYP2"');

        $this->getEtagOfElement(self::TOKEN, self::ID_OWNS, '', '"6CXMOWXmrBs"');

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
