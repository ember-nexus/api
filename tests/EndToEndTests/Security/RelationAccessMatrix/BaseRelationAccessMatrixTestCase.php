<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * Access to a relation depends on the user's access to its start and end node, see the scenario
 * "security.relationAccessMatrix" of the reference dataset.
 *
 * The side of a relation is described by a code of six letters, in the fixed order O C R U D S (OWNS and
 * HAS_{CREATE,READ,UPDATE,DELETE,SEARCH}_ACCESS). A letter means that the user has this access relation to the node, X
 * means that it is absent: "OXXXXX" owns only, "XXXUXX" has update access only.
 *
 * For a relation start -> end:
 *
 * - GET:          start needs O or R, end needs O or R
 * - POST:         start needs O or C, end needs O or R (the new relation connects the same two nodes)
 * - PATCH, PUT:   start needs O or U, end needs O or R
 * - DELETE:       start needs O or D, end needs O or R
 *
 * Everything else is answered with 404. Every pair of codes has its own relation in the dataset, therefore all pairs
 * can be tested independently of each other and in parallel; the tests expect a freshly imported dataset, as the
 * DELETE request removes the relation.
 */
abstract class BaseRelationAccessMatrixTestCase extends BaseRequestTestCase
{
    protected const string TOKEN = 'secret-token:XFXbImPnyORfLPqaH7jBUr';

    protected function assertRelationAccess(
        string $startCode,
        string $endCode,
        string $relationId,
        string $startId,
        string $endId,
    ): void {
        $endIsReadable = $this->hasAny($endCode, 'OR');

        $response = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN);
        if ($this->hasAny($startCode, 'OR') && $endIsReadable) {
            $this->assertIsRelationResponse($response, 'RELATION');
        } else {
            $this->assertIsProblemResponse($response, 404);
        }

        $response = $this->runPostRequest('/', self::TOKEN, [
            'type' => 'RELATION',
            'start' => $startId,
            'end' => $endId,
        ]);
        if ($this->hasAny($startCode, 'OC') && $endIsReadable) {
            $this->assertIsCreatedResponse($response);
        } else {
            $this->assertIsProblemResponse($response, 404);
        }

        $canUpdate = $this->hasAny($startCode, 'OU') && $endIsReadable;
        $response = $this->runPatchRequest(sprintf('/%s', $relationId), self::TOKEN, ['patched' => true]);
        $canUpdate ? $this->assertNoContentResponse($response) : $this->assertIsProblemResponse($response, 404);
        $response = $this->runPutRequest(sprintf('/%s', $relationId), self::TOKEN, ['replaced' => true]);
        $canUpdate ? $this->assertNoContentResponse($response) : $this->assertIsProblemResponse($response, 404);

        // last, as it removes the relation
        $response = $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        if ($this->hasAny($startCode, 'OD') && $endIsReadable) {
            $this->assertIsDeletedResponse($response);
        } else {
            $this->assertIsProblemResponse($response, 404);
        }
    }

    /**
     * True if the code contains at least one of the given letters.
     */
    private function hasAny(string $code, string $letters): bool
    {
        return 1 === preg_match(sprintf('/[%s]/', $letters), $code);
    }
}
