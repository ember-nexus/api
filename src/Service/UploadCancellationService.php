<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\UploadInterface;
use App\Factory\Type\UploadFactory;

/**
 * An upload only makes sense as long as its owner can update the target element; once that access is lost, the upload
 * is cancelled, i.e. its chunks and its `Upload` element are deleted.
 */
class UploadCancellationService
{
    public function __construct(
        private ElementManager $elementManager,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadLockService $uploadLockService,
    ) {
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
}
