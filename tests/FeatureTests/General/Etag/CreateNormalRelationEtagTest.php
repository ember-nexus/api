<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class CreateNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:49pU9scpisIXhS3SQNTn97';
    private const string UUID_DATA_1 = '106e6b00-4026-462b-9394-e7da4bc777ed';
    private const string UUID_DATA_2 = '89ecbd25-0402-468f-af0c-3f307fff5b9f';

    private function testEtagOfElement(string $token, string $uuid, string $additionalPath, ?string $shouldEtag = null): string
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $uuid, $additionalPath),
            $token
        );
        $etag = $response->getHeader('Etag')[0];
        if ($shouldEtag) {
            $this->assertSame($shouldEtag, $etag);
        }

        return $etag;
    }

    public function testEtagBeforeAndAfterCreatingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"9GlvHTmFZ2A"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"WmHqlOkHlae"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"Tjmj9SoBVlB"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"WmHqlOkHlae"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', '"CskIAOFnbGs"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', '"OiMnEKa7IMu"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', '"GhKsdR5WJGS"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', '"OiMnEKa7IMu"');

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'start' => self::UUID_DATA_1,
                'end' => self::UUID_DATA_2,
                'data' => [
                    'scenario' => 'general.etag.create-normal-relation',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', $initialEtagNode1Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', $initialEtagNode1Children);
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', $initialEtagNode2Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', $initialEtagNode2Parents);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);
    }
}
