<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * `If-Match` and `If-None-Match` sent together on the same request (RFC 9110, Section 13.2.2): `If-Match` is
 * evaluated first, and only if it is satisfied is `If-None-Match` evaluated. A failing `If-Match` therefore always
 * wins with `412`, regardless of `If-None-Match`; a satisfied `If-Match` combined with a matching `If-None-Match`
 * gives `304` on `GET`/`HEAD` (`412` on a modifying request), and a satisfied `If-Match` combined with a
 * non-matching `If-None-Match` continues as a normal request.
 */
class IfMatchAndIfNoneMatchCombinedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createElementAndGetEtag(string $name): array
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $etag = $response->getHeader('ETag')[0];

        return [$elementId, $etag];
    }

    public function testBothMatchGivesNotModified(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-and-if-none-match-both-match');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => $etag,
            'If-None-Match' => $etag,
        ]);
        $this->assertNotModifiedResponse($response);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testIfMatchFailingWinsOverMatchingIfNoneMatch(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-fails-if-none-match-matches');

        // If-Match is evaluated first and fails: the request must get 412, not the 304 that If-None-Match alone
        // would give
        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => '"wrongEtag"',
            'If-None-Match' => $etag,
        ]);
        $this->assertIsProblemResponse($response, 412);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testBothFailingGives412FromIfMatch(): void
    {
        [$elementId] = $this->createElementAndGetEtag('if-match-and-if-none-match-both-fail');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => '"wrongEtag"',
            'If-None-Match' => '"alsoWrongEtag"',
        ]);
        $this->assertIsProblemResponse($response, 412);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testIfMatchSatisfiedAndIfNoneMatchNotMatchingContinuesNormally(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-matches-if-none-match-does-not');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => $etag,
            'If-None-Match' => '"wrongEtag"',
        ]);
        $this->assertIsNodeResponse($response, 'Data');

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testWildcardIfMatchWithMatchingIfNoneMatchGivesNotModified(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-wildcard-if-none-match-matches');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => '*',
            'If-None-Match' => $etag,
        ]);
        $this->assertNotModifiedResponse($response);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testMatchingIfMatchWithWildcardIfNoneMatchGivesNotModified(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-matches-if-none-match-wildcard');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => $etag,
            'If-None-Match' => '*',
        ]);
        $this->assertNotModifiedResponse($response);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testFailingIfMatchWithWildcardIfNoneMatchStillGives412(): void
    {
        [$elementId] = $this->createElementAndGetEtag('if-match-fails-if-none-match-wildcard');

        // the wildcard If-None-Match would be satisfied by any current representation, but If-Match is evaluated
        // first and fails, so the request must never reach the point where the wildcard could turn it into a 304
        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN, [
            'If-Match' => '"wrongEtag"',
            'If-None-Match' => '*',
        ]);
        $this->assertIsProblemResponse($response, 412);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testBothMatchingOnModifyingRequestGives412NotNotModified(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-and-if-none-match-both-match-patch');

        // 304 only applies to GET/HEAD; a modifying request with a matching If-None-Match gives 412 instead, even
        // though If-Match (evaluated first) is satisfied
        $response = $this->runPatchRequest(sprintf('/%s', $elementId), self::TOKEN, ['name' => 'changed'], [
            'If-Match' => $etag,
            'If-None-Match' => $etag,
        ]);
        $this->assertIsProblemResponse($response, 412);

        // nothing was changed by the rejected request
        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN));
        $this->assertSame('if-match-and-if-none-match-both-match-patch', $body['data']['name']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testIfMatchFailingOnModifyingRequestWinsOverMatchingIfNoneMatch(): void
    {
        [$elementId, $etag] = $this->createElementAndGetEtag('if-match-fails-if-none-match-matches-patch');

        $response = $this->runPatchRequest(sprintf('/%s', $elementId), self::TOKEN, ['name' => 'changed'], [
            'If-Match' => '"wrongEtag"',
            'If-None-Match' => $etag,
        ]);
        $this->assertIsProblemResponse($response, 412);

        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN));
        $this->assertSame('if-match-fails-if-none-match-matches-patch', $body['data']['name']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
