<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteNormalRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:61GotFGNgBa3YmB8UXU1l2';
    private const string UUID_DATA_1 = '572826c4-f041-4265-be18-4de5b6f4a6bc';
    private const string UUID_DATA_2 = '119cdc2a-e169-4cc5-a20a-7d6b67e05c25';
    private const string UUID_RELATED = '41e07860-c278-4d3a-b96a-47465e832b5e';

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

    public function testEtagBeforeAndAfterDeletingCentralNormalRelation(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"TAtItfs3idO"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"FbiZIdWq12P"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"YSITtXY7GMb"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"Z6W1WX1iL6K"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', '"DMa9WhPTKXt"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', '"AqEB7dVQdPC"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', '"EQP6CSWfYKi"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', '"E5sTUk3DppO"');

        $this->testEtagOfElement(self::TOKEN, self::UUID_RELATED, '', '"KQmZRZRdpic"');

        $response = $this->runDeleteRequest(
            sprintf(
                '%s',
                self::UUID_RELATED
            ),
            self::TOKEN
        );
        $this->assertNoContentResponse($response);

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
