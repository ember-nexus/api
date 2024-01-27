<?php

namespace App\tests\FeatureTests\General\Etag;

use App\Tests\FeatureTests\BaseRequestTestCase;

class UpdateOwnsRelationEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:7Am3XPcbtViQSo36A67DsS';
    private const string UUID_DATA_1 = '32d7bc37-155b-44bd-9e7c-f0a1c1230210';
    private const string UUID_DATA_2 = 'f8ebe840-387d-4e35-b76d-1e367e6b2b2d';
    private const string UUID_OWNS = '57f312c3-7536-43dc-92df-06b5ef3b87ee';

    private function testEtagOfElement(string $token, string $uuid, string $additionalPath, string $shouldEtag = null): string
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

    public function testEtagBeforeAndAfterUpdatingCentralOwnsRelation(): void
    {
        $initialEtagNode1Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', '"fkFkIGpTl6c"');
        $initialEtagNode1Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', '"YJGQbkhuZfp"');
        $initialEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children', '"NbLVLXrHmDD"');
        $initialEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related', '"ec9LJ6k37vU"');

        $initialEtagNode2Self = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', '"PUcedOCOgd0"');
        $initialEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents', '"PC44jjDQsk"');
        $initialEtagNode2Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', '"A7346CqlZKv"');
        $initialEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related', '"PC44jjDQsk"');

        $initialEtagOwnsSelf = $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS, '', '"FbSsUAQKn2s"');

        $response = $this->runPatchRequest(
            sprintf(
                '%s',
                self::UUID_OWNS
            ),
            self::TOKEN,
            [
                'some' => 'changed data',
            ]
        );
        $this->assertNoContentResponse($response);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '', $initialEtagNode1Self);
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/parents', $initialEtagNode1Parents);
        $finalEtagNode1Children = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/children');
        $finalEtagNode1Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_1, '/related');

        $this->assertNotSame($initialEtagNode1Children, $finalEtagNode1Children);
        $this->assertNotSame($initialEtagNode1Related, $finalEtagNode1Related);

        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '', $initialEtagNode2Self);
        $finalEtagNode2Parents = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/parents');
        $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/children', $initialEtagNode2Children);
        $finalEtagNode2Related = $this->testEtagOfElement(self::TOKEN, self::UUID_DATA_2, '/related');

        $this->assertNotSame($initialEtagNode2Parents, $finalEtagNode2Parents);
        $this->assertNotSame($initialEtagNode2Related, $finalEtagNode2Related);

        $finalEtagOwnsSelf = $this->testEtagOfElement(self::TOKEN, self::UUID_OWNS, '');

        $this->assertNotSame($initialEtagOwnsSelf, $finalEtagOwnsSelf);
    }
}
