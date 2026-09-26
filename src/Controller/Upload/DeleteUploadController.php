<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\UploadLockService;
use App\Service\UploadService;
use App\Type\Response\NoContentResponse;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DeleteUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private ElementManager $elementManager,
        private LoggerInterface $logger,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadLockService $uploadLockService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    #[Route(
        '/upload/{id}',
        name: 'delete-upload',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['DELETE']
    )]
    public function deleteUpload(string $id): NoContentResponse
    {
        $uploadElement = $this->elementManager->getElementOrFail(UuidV4::fromString($id));
        try {
            $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
        } catch (Exception) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($upload->getUploadOwner()->toString() !== $this->authProvider->getUserId()->toString()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $this->logger->info(
            'Deleting upload element from database and S3.',
            [
                'uploadId' => $upload->getId()->toString(),
                'elementId' => $upload->getUploadTarget()->toString(),
            ]
        );

        // must not race an append, which would recreate chunks or the node after the deletion
        $lockToken = $this->uploadLockService->acquire($upload->getId());
        if (null === $lockToken) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Another request is currently modifying this upload, please retry once it has finished.');
        }

        try {
            // an append may have finished while waiting for the lock, chunk count has to be fresh
            $upload = $this->uploadFactory->createUploadFromElement($this->elementManager->getElementOrFail(UuidV4::fromString($id)));
            $this->uploadService->deleteUploadAndChunks($upload);
            $this->elementManager->flush();
        } finally {
            $this->uploadLockService->release($upload->getId(), $lockToken);
        }

        return new NoContentResponse();
    }
}
