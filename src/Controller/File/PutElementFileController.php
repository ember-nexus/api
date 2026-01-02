<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
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

        return $this->uploadCreationService->handleUploadCreationFromRequest($elementId, $request);
    }
}
