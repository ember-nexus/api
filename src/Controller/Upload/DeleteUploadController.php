<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\S3Service;
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
        private S3Service $s3Service,
        private FileOperationFactory $fileOperationFactory,
        private UploadFactory $uploadFactory,
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

        if ($upload->getUploadOwner() !== $this->authProvider->getUserId()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $this->logger->info(
            'Deleting upload element from database and S3.',
            [
                'uploadId' => $upload->getId()->toString(),
                'elementId' => $upload->getUploadTarget()->toString(),
            ]
        );

        for ($i = 0; $i <= $upload->getAlreadyUploadedChunks(); ++$i) {
            $deleteChunkOperation = $this->fileOperationFactory->createFileOperationFromUpload($upload, $i);
            $this->s3Service->deleteFile($deleteChunkOperation);
        }

        $this->elementManager->delete($uploadElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
