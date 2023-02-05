<?php

namespace App\Controller;

use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\ElementResponseService;
use App\Service\ProblemJsonGeneratorService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetUuidController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private ProblemJsonGeneratorService $problemJsonGeneratorService,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getUuid',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuid(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $hasPermission = $this->permissionChecker->checkPermissionToElement(
            $this->authProvider->getUserUuid(),
            $elementUuid,
            'READ'
        );
        if (!$hasPermission) {
            return $this->problemJsonGeneratorService->createProblemJsonFor404();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($elementUuid);
    }
}
