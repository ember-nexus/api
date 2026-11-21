<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class DeleteNodeEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:Tn2aRIttthKXKaAqUXjId';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLAttBOxS4SdaHrptl5iK5ks1';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLAjfDCCvtEAl7QPLRSmkNYhz';
    private const string ID_DATA_3 = '02IiNr0EPf9nVLCCZaJdkHQz66bpmlslLzBrt';
    private const string ID_DATA_4 = '02IiNr0EPf9nVLAoo32uYjSophepTBYx1vM6k';
    private const string ID_DATA_5 = '02IiNr0EPf9nVLAFYw0QpOeKExPPdPPPKYzwy';

    public function testEtagBeforeAndAfterDeletingCentralNode(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"1GMrMmBGvYR"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"FM4Wn2ZIBGa"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"6YYOEoa6FYY"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"CjEbu1FORAP"');

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"cZetdQRPAjb"');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"blS6YXfbFpq"');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"TqoP6Fu87eK"');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"K18iBuuEipB"');

        $initialEtagNode3Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '', '"DA3cUbWdrr7"');
        $initialEtagNode3Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/parents', '"N9mqRncMNbf"');
        $initialEtagNode3Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/children', '"co8KfVvE6QA"');
        $initialEtagNode3Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_3, '/related', '"N9mqRncMNbf"');

        $initialEtagNode4Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '', '"8sFTi10edYY"');
        $initialEtagNode4Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/parents', '"L6kRfoqr5Z"');
        $initialEtagNode4Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/children', '"WPZbd7KZMlX"');
        $initialEtagNode4Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_4, '/related', '"YkKeeqJnDd9"');

        $initialEtagNode5Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '', '"YeN8vv2ta8L"');
        $initialEtagNode5Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/parents', '"QiAJblaIILo"');
        $initialEtagNode5Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/children', '"Xas0vkg9PLM"');
        $initialEtagNode5Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_5, '/related', '"4NUMBMkR6un"');

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::ID_DATA_2
            ),
            self::TOKEN
        );
        $this->assertIsDeletedResponse($response);

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
