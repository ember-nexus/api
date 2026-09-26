<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\FileCreationLockService;
use App\Service\UploadCreationService;
use App\Type\AccessType;
use App\Type\EtagType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class PutElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private UploadCreationService $uploadCreationService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private FileCreationLockService $fileCreationLockService,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'put-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    #[EndpointSupportsEtag(EtagType::FILE)]
    public function putElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        // parallel PUT requests may race each other, but not a running POST which creates the file
        if ($this->fileCreationLockService->isLocked($elementId)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Another request is currently creating the file of element with id '%s'.", $elementId->toString()));
        }

        return $this->uploadCreationService->handleUploadCreationFromRequest($elementId, $request);
    }
}
