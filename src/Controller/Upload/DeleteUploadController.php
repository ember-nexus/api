<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\ElementManager;
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
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
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
        } catch (Exception $e) {
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

        $this->uploadService->deleteUploadAndChunks($upload);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
