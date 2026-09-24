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
     * Does not flush, same as {@see deleteUpload()}.
     */
    public function deleteUploadAndChunks(UploadInterface $upload): void
    {
        // chunk keys start at 1; the key after the last accepted chunk may hold a chunk which was rejected after upload
        for ($chunk = 1; $chunk <= $upload->getAlreadyUploadedChunks() + 1; ++$chunk) {
            $deleteChunkOperation = $this->fileOperationFactory->createFileOperationFromUpload($upload, $chunk);
            $this->s3Service->deleteFile($deleteChunkOperation);
        }

        $this->deleteUpload($upload);
    }

    /**
     * Deletes uploads targeting the element, as `uploadTarget` is a plain property and not a relation. Must be
     * called and flushed before the element itself is deleted; it is not an event listener because nested
     * `flush()` calls are not supported.
     */
    public function deleteUploadsTargeting(UuidInterface $elementId): void
    {
        foreach ($this->getUploadIdsTargeting($elementId) as $uploadId) {
            $uploadElement = $this->elementManager->getElement(UuidV4::fromString($uploadId));
            if (null === $uploadElement) {
                continue;
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
