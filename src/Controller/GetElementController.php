<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\ElementResponseService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getElement',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $hasPermission = $this->permissionChecker->checkPermissionToElement(
            $this->authProvider->getUserUuid(),
            $elementUuid,
            'READ'
        );
        if (!$hasPermission) {
            throw new ClientNotFoundException();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($elementUuid);
    }
}
