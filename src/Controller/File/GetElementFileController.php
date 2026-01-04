<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\BinaryStreamResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\FileUtilService;
use App\Service\StorageUtilService;
use App\Type\AccessType;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class GetElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private StorageUtilService $storageUtilService,
        private FileUtilService $fileUtilService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'get-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElementFile(string $id): BinaryStreamResponse
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $fileName = $this->fileUtilService->getFileName($element);

        $objectConfig = [
            'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
            'Key' => $this->storageUtilService->getStorageBucketKey($elementId),
        ];
        $status = $this->s3Client->objectExists($objectConfig);

        if (!$status->isSuccess()) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $object = $this->s3Client->getObject($objectConfig);

        return new BinaryStreamResponse($object, $fileName);
    }
}
