<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Type\NodeElement;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadService
{
    public function __construct(
        private ElementManager $elementManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private UploadFactory $uploadFactory,
        private CypherEntityManager $cypherEntityManager,
    ) {
    }

    public function mergeUploadElement(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElement($upload->getId());
        if (null !== $element) {
            if (!($element instanceof NodeElementInterface)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected upload element to be a node, received %s.', get_debug_type($element)));
            }
            if ('Upload' !== $element->getLabel()) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected upload element to be of type 'Upload', not '%s'.", $element->getLabel() ?? 'null'));
            }
        } else {
            $element = (new NodeElement())
                ->setId($upload->getId())
                ->setLabel('Upload');
        }

        $element->addProperty('uploadLength', $upload->getUploadLength());
        $element->addProperty('uploadOffset', $upload->getUploadOffset());
        $element->addProperty('uploadComplete', $upload->isUploadComplete());
        $element->addProperty('uploadTarget', $upload->getUploadTarget()->toString());
        $element->addProperty('alreadyUploadedChunks', $upload->getAlreadyUploadedChunks());
        $element->addProperty('uploadOwner', $upload->getUploadOwner()->toString());
        $element->addProperty('extension', $upload->getExtension());
        $element->addProperty('expires', $upload->getExpires());
        $element->addProperty('hashState', $upload->getHashState());

        $this->elementManager->merge($element);
    }

    public function deleteUpload(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getId());

        $this->elementManager->delete($element);
    }

    /**
     * Deletes an upload's already-uploaded chunks from S3, then the `Upload` element itself. Does not flush -
     * that is left to the caller, matching {@see deleteUpload()}.
     */
    public function deleteUploadAndChunks(UploadInterface $upload): void
    {
        for ($chunk = 0; $chunk <= $upload->getAlreadyUploadedChunks(); ++$chunk) {
            $deleteChunkOperation = $this->fileOperationFactory->createFileOperationFromUpload($upload, $chunk);
            $this->s3Service->deleteFile($deleteChunkOperation);
        }

        $this->deleteUpload($upload);
    }

    /**
     * Deletes any in-progress resumable upload(s) still targeting `$elementId`, and their already-uploaded S3
     * chunks - meant to be called (and flushed) *before* the target element itself is deleted. `uploadTarget`
     * (see {@see mergeUploadElement()}) is a plain property, not a graph edge, so deleting an element does not
     * take any upload(s) still targeting it along with it on its own; without this, they would linger - still
     * HEAD-able, still occupying storage - until the `cron:delete-expired-uploads` grace period eventually
     * catches up with them.
     *
     * Deliberately not wired up as an event listener on element deletion: an `Upload` element deleted by this
     * method would itself re-enter `ElementManager::delete()`/`flush()` while the *original* deletion's own
     * `flush()` for the target element is still on the call stack (event listeners fire from within `flush()`) -
     * `ElementManager` and the underlying entity managers are not reentrant, and a nested `flush()` call
     * corrupts the outer one's still-in-flight batch. Calling this as an explicit, separate, sequential step
     * before the target's own delete+flush avoids that entirely.
     */
    public function deleteUploadsTargeting(UuidInterface $elementId): void
    {
        foreach ($this->getUploadIdsTargeting($elementId) as $uploadId) {
            $uploadElement = $this->elementManager->getElement(UuidV4::fromString($uploadId));
            if (null === $uploadElement) {
                continue; // already gone (e.g. deleted or expired in the meantime), nothing left to do
            }

            try {
                $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
            } catch (Exception) {
                continue; // @codeCoverageIgnore
            }

            $this->deleteUploadAndChunks($upload);
        }
    }

    /**
     * @return string[]
     */
    private function getUploadIdsTargeting(UuidInterface $elementId): array
    {
        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH (u:Upload) WHERE u.uploadTarget = $elementId RETURN u.id',
            [
                'elementId' => $elementId->toString(),
            ]
        ));

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
