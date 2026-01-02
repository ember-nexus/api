<?php

declare(strict_types=1);

namespace App\Controller\Upload;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\StorageUtilService;
use App\Type\UploadElement;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DeleteUploadController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private S3Client $s3Client,
        private ElementManager $elementManager,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private StorageUtilService $storageUtilService,
        private LoggerInterface $logger,
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
        $uploadId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        $uploadElement = $this->elementManager->getElement($uploadId);
        if (null === $uploadElement) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        try {
            $uploadElement = UploadElement::createFromElement($uploadElement);
        } catch (Exception $e) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (null === $uploadElement->getUploadOwner()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if ($uploadElement->getUploadOwner() !== $userId) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $this->logger->info(
            'Deleting upload element from database and S3.',
            [
                'uploadId' => $uploadId->toString(),
                'elementId' => false,
            ]
        );

        $uploadBucket = $this->emberNexusConfiguration->getFileS3UploadBucket();
        for ($i = 0; $i <= $uploadElement->getAlreadyUploadedChunks(); ++$i) {
            $objectConfig = [
                'Bucket' => $uploadBucket,
                'Key' => $this->storageUtilService->getUploadBucketKey($uploadId, $i),
            ];
            $status = $this->s3Client->objectExists($objectConfig);

            if ($status->isSuccess()) {
                $this->s3Client->deleteObject($objectConfig);
            }
        }

        $this->elementManager->delete($uploadElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
