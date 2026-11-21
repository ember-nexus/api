<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Etag;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class CreateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:49pU9scpisIXhS3SQNTn97';
    private const string ID_DATA_1 = '02IiNr0EPf9nVLAMG8YZbzhLQR7uvOazlsDQh';
    private const string ID_DATA_2 = '02IiNr0EPf9nVLBJQOsBSvslHQHWErWyQxWyd';

    public function testEtagBeforeAndAfterCreatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"BnfJAY4dkvY"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"1hFIrddM5TT"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"8FoC6Ah0Sj3"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"1hFIrddM5TT"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"alIbC8cSu5I"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"4E88oWJIWcS"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"EhpoR5IRsij"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"4E88oWJIWcS"');

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'start' => self::ID_DATA_1,
                'end' => self::ID_DATA_2,
                'data' => [
                    'scenario' => 'general.etag.create-normal-relation',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

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
