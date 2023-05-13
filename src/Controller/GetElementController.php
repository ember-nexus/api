<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::READ)) {
            throw new ClientNotFoundException();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($elementUuid);
    }
}
