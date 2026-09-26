<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\UploadInterface;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\AccessType;
use Exception;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Safe\DateTime;

/**
 * Loads an upload for its owner: everybody else, and the owner after losing access to the target, only get a 404.
 */
class UploadAccessService
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UploadFactory $uploadFactory,
        private UploadCancellationService $uploadCancellationService,
        private UploadConsistencyService $uploadConsistencyService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client410GoneExceptionFactory $client410GoneExceptionFactory,
    ) {
    }

    public function loadAuthorizedUpload(string $id): UploadInterface
    {
        $uploadElement = $this->elementManager->getElementOrFail(UuidV4::fromString($id));
        try {
            $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
        } catch (Exception) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $userId = $this->authProvider->getUserId();
        if ($upload->getUploadOwner()->toString() !== $userId->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        if (!$this->accessChecker->hasAccessToElement($userId, $upload->getUploadTarget(), AccessType::UPDATE)) {
            // the owner lost access to the target, so the upload is cancelled as well
            $this->uploadCancellationService->cancelUpload($upload);

            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        // an expired upload is gone even if the cron job did not remove it yet
        if ($upload->getExpires() < new DateTime()) {
            throw $this->client410GoneExceptionFactory->createFromTemplate();
        }
        $this->uploadConsistencyService->assertConsistent($uploadElement, $upload);

        return $upload;
    }
}
