<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\ElementManager;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteUuidController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'deleteUuid',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['DELETE']
    )]
    public function deleteUuid(string $uuid, Request $request): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $hasReadPermission = $this->permissionChecker->checkPermissionToElement(
            $this->authProvider->getUserUuid(),
            $elementUuid,
            'READ'
        );
        $hasDeletePermission = $this->permissionChecker->checkPermissionToElement(
            $this->authProvider->getUserUuid(),
            $elementUuid,
            'DELETE'
        );
        if (!$hasDeletePermission) {
            if (!$hasReadPermission) {
                throw new ClientNotFoundException();
            }
            throw new ClientUnauthorizedException(detail: 'You do not have permission to delete the requested resource.');
        }

        $element = $this->elementManager->getElement($elementUuid);
        if (null === $element) {
            throw new ClientNotFoundException();
        }
        $this->elementManager->delete($element);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
