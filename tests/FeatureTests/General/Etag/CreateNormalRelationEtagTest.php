<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class CreateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:49pU9scpisIXhS3SQNTn97';
    private const string ID_DATA_1 = '106e6b00-4026-462b-9394-e7da4bc777ed';
    private const string ID_DATA_2 = '89ecbd25-0402-468f-af0c-3f307fff5b9f';


    public function testEtagBeforeAndAfterCreatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"9GlvHTmFZ2A"');
        $initialEtagNode1Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"WmHqlOkHlae"');
        $initialEtagNode1Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"Tjmj9SoBVlB"');
        $initialEtagNode1Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"WmHqlOkHlae"');

        $initialEtagNode2Self = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"CskIAOFnbGs"');
        $initialEtagNode2Parents = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"OiMnEKa7IMu"');
        $initialEtagNode2Children = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"GhKsdR5WJGS"');
        $initialEtagNode2Related = $this->getEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"OiMnEKa7IMu"');

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
