<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use App\Type\AccessType;
use App\Type\EtagType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'get-element',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    #[EndpointSupportsEtag(EtagType::ELEMENT)]
    public function getElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($elementUuid);
    }
}
