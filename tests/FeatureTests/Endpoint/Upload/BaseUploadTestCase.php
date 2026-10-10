<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Shared helpers of the upload endpoint tests, which commonly need to run the same scenario against both a node and
 * a relation target.
 */
abstract class BaseUploadTestCase extends BaseRequestTestCase
{
    protected const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    protected const int CHUNK_SIZE = 5 * 1024 * 1024;

    protected function createTarget(bool $onRelation, string $name): string
    {
        if ($onRelation) {
            return $this->createEphemeralRelation(self::TOKEN, $name);
        }

        return $this->createElement(self::TOKEN, $name);
    }

    protected function deleteTarget(bool $onRelation, string $elementId): void
    {
        if ($onRelation) {
            $this->deleteEphemeralRelation(self::TOKEN, $elementId);
        } else {
            $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
        }
    }
}
