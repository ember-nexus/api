<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Type\AccessType;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * An upload only makes sense as long as its owner can update the target element; once that access is lost, the upload
 * is cancelled, i.e. its chunks and its `Upload` element are deleted.
 *
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadCancellationService
{
    public function __construct(
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadLockService $uploadLockService,
        private CypherEntityManager $cypherEntityManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Cancels all uploads whose owner can no longer update the target element.
     *
     * @return int number of cancelled uploads
     */
    public function cancelUploadsWithoutAccess(): int
    {
        $cancelledUploads = 0;
        foreach ($this->getUploadIds() as $uploadId) {
            $uploadElement = $this->elementManager->getElement(UuidV4::fromString($uploadId));
            if (null === $uploadElement) {
                continue;
            }
            try {
                $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
            } catch (Exception) {
                continue;
            }
            if ($this->accessChecker->hasAccessToElement($upload->getUploadOwner(), $upload->getUploadTarget(), AccessType::UPDATE)) {
                continue;
            }
            if ($this->cancelUpload($upload)) {
                ++$cancelledUploads;
            }
        }

        return $cancelledUploads;
    }

    /**
     * Returns false if the upload is currently locked by another request; it is then cancelled by a later review.
     */
    public function cancelUpload(UploadInterface $upload): bool
    {
        $lockToken = $this->uploadLockService->acquire($upload->getId());
        if (null === $lockToken) {
            return false;
        }

        try {
            // an append may have finished while waiting for the lock, chunk count has to be fresh
            $uploadElement = $this->elementManager->getElement($upload->getId());
            if (null !== $uploadElement) {
                $this->uploadService->deleteUploadAndChunks($this->uploadFactory->createUploadFromElement($uploadElement));
                $this->elementManager->flush();
            }
        } finally {
            $this->uploadLockService->release($upload->getId(), $lockToken);
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getUploadIds(): array
    {
        $queryResult = $this->cypherEntityManager->getClient()->runStatement(Statement::create('MATCH (u:Upload) RETURN u.id'));

        $uploadIds = [];
        foreach ($queryResult as $queryResultLine) {
            $uploadId = $queryResultLine['u.id'];
            if (!is_string($uploadId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property u.id as string, not %s.', get_debug_type($uploadId))); // @codeCoverageIgnore
            }
            $uploadIds[] = $uploadId;
        }

        return $uploadIds;
    }
}
