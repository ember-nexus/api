<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileCreationLockService;
use App\Service\UploadCreationService;
use App\Service\UploadService;
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
class PostElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private UploadCreationService $uploadCreationService,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private FileCreationLockService $fileCreationLockService,
        private UploadService $uploadService,
    ) {
    }

    #[Route(
        '/{id}/file',
        name: 'post-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    #[EndpointSupportsEtag(EtagType::FILE)]
    public function postElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        // POST creates the file: only one creation per element at a time, the lock is released as soon as the request
        // ends (also on errors), or expires on its own if the process dies
        $lockToken = $this->fileCreationLockService->acquire($elementId);
        if (null === $lockToken) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Another request is currently creating the file of element with id '%s'.", $elementId->toString()));
        }

        try {
            $element = $this->elementManager->getElementOrFail($elementId);
            if ($this->elementService->hasFile($element)) {
                throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Element with id '%s' already has an associated file; can not create new file. Delete existing file first or replace it with PUT.", $element->getId()?->toString() ?? 'missing element id'));
            }
            if ($this->uploadService->hasUploadsTargeting($elementId)) {
                throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("An upload for element with id '%s' is in progress; can not create new file. Finish or delete the upload first.", $elementId->toString()));
            }

            return $this->uploadCreationService->handleUploadCreationFromRequest($elementId, $request);
        } finally {
            $this->fileCreationLockService->release($elementId, $lockToken);
        }
    }
}
