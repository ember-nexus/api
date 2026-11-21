<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class DeleteNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:61GotFGNgBa3YmB8UXU1l2';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLAui90UhppgOI7ck75pEMAMa';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLAMpyhOLY3hc3YLwuh6odOHO';
    private const string ID_RELATED = '02IiNr0EPf9nVLAkMW6bKtYaNZjfD8WcbD3kq';

    public function testEtagBeforeAndAfterDeletingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"eGUt3Qaj5mL"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"c1BrtOaZJTE"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"67X2frReXZL"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"b6g30RJLSm1"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"XW7CAXbnYCV"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"iAbSfYAGvk"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"RGXV4Cv3DS8"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"GtkAJPB4v8j"');

        $this->getEtagOfElement(self::TOKEN, self::ID_RELATED, '', '"RmsBWn5rrIn"');

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::ID_RELATED
            ),
            self::TOKEN
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
    }
}
