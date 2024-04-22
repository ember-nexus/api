<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class CreateOwnsRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:9M0TgcI5840lflFfU81u9i';
    private const string ID_DATA_1 = 'd62f5169-1e12-42eb-ac91-8a6cfa9b3244';
    private const string ID_DATA_2 = 'a0691ac0-b75b-4c4f-9fe0-a95b30263e10';

    private function testEtagOfElement(string $token, string $id, string $additionalPath, ?string $shouldEtag = null): string
    {
        $response = $this->runGetRequest(
            sprintf('/%s%s', $id, $additionalPath),
            $token
        );
        $etag = $response->getHeader('Etag')[0];
        if ($shouldEtag) {
            $this->assertSame($shouldEtag, $etag);
        }

        return $etag;
    }

    public function testEtagBeforeAndAfterCreatingCentralOwnsRelation(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '', '"Sv20bjBNB4C"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', '"RT5DNLKkjYu"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children', '"JMkheNkHcMY"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related', '"RT5DNLKkjYu"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '', '"cppdLnhCrkB"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents', '"QpQpqTtRbQ0"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', '"cr9HksHQb3o"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related', '"QpQpqTtRbQ0"');

        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'OWNS',
                'start' => self::ID_DATA_1,
                'end' => self::ID_DATA_2,
                'data' => [
                    'scenario' => 'general.etag.create-owns-relation',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/children');
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '', $initialEtagNode2Self);
        $finalEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/parents');
        $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::ID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Parents, $finalEtagNode2Parents);
        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);
    }
}
