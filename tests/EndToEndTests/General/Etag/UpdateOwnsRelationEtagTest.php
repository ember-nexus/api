<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class UpdateOwnsRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:7Am3XPcbtViQSo36A67DsS';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLAd1oH698TTmiWmNEmKSFjZh';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLCBTerjK3kykorm75pIfUa0S';
    private const string ID_OWNS = '02IiNr0EPf9nVLAv6daQQGzhCa3bX0TDQeZbS';

    public function testEtagBeforeAndAfterUpdatingCentralOwnsRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"R7Sl3j9rVvL"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"5EQJgKvgCS7"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"LO0cmkkRCkC"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"G1bhNis7ZUT"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"aXlQ3bJdceW"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"IYq6DcJMqbq"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"e721JK1npaD"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"IYq6DcJMqbq"');

        $initialEtagOwnsSelf = $this->getEtagOfElement(self::TOKEN, self::ID_OWNS, '', '"9vJeLIMKYuY"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::ID_OWNS
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

        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', $initialEtagNode2Self);
        $finalEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents');
        $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Parents, $finalEtagNode2Parents);
        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);

        $finalEtagOwnsSelf = $this->getEtagOfElement(self::TOKEN, self::ID_OWNS, '');

        $this->assertNotSame($initialEtagOwnsSelf, $finalEtagOwnsSelf);
    }
}
