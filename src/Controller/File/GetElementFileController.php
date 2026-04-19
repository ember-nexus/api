<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Service\S3Service;
use App\Type\AccessType;
use App\Type\EtagType;
use App\Type\Response\BinaryStreamResponse;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class GetElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private FileService $fileService,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
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
    #[EndpointSupportsEtag(EtagType::FILE)]
    public function getElementFile(string $id): BinaryStreamResponse
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElementOrFail($elementId);

        $fileName = $this->elementService->getFileName($element);
        $fileNameFallback = $this->fileService->getAsciiSafeFileName($fileName);

        $fileOperation = $this->fileOperationFactory->createFileOperationFromElement($element);

        $doesFileExist = $this->s3Service->existsFile($fileOperation);
        if (false === $doesFileExist) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $object = $this->s3Service->getFile($fileOperation);

        return new BinaryStreamResponse($object, $fileName, $fileNameFallback);
    }
}
