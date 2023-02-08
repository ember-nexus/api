<?php

namespace App\Controller;

use App\Exception\ClientBadRequestException;
use App\Exception\ClientForbiddenException;
use App\Exception\ClientNotFoundException;
use App\Response\CreatedResponse;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\ElementManager;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostIndexController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/',
        name: 'postIndex',
        methods: ['POST']
    )]
    public function postIndex(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $elementId = null;
        if (array_key_exists('id', $body)) {
            $elementId = UuidV4::fromString($body['id']);
        }

        $startId = null;
        if (array_key_exists('start', $body)) {
            $startId = UuidV4::fromString($body['start']);
        }

        $endId = null;
        if (array_key_exists('end', $body)) {
            $endId = UuidV4::fromString($body['end']);
        }

        if (null === $startId && null !== $endId) {
            throw new ClientBadRequestException(detail: "When creating a relation, both properties 'start' as well as 'end' must be set.");
        }

        if (null !== $startId && null === $endId) {
            throw new ClientBadRequestException(detail: "When creating a relation, both properties 'start' as well as 'end' must be set.");
        }

        if (null !== $startId && null !== $endId) {
            $createAtStartPermission = $this->permissionChecker->checkPermissionToElement(
                $this->authProvider->getUserUuid(),
                $startId,
                'CREATE'
            );
            if (!$createAtStartPermission) {
                $readStartPermission = $this->permissionChecker->checkPermissionToElement(
                    $this->authProvider->getUserUuid(),
                    $startId,
                    'READ'
                );
                if ($readStartPermission) {
                    throw new ClientForbiddenException(detail: sprintf("User does not have permissions to create relationship starting at node with id '%s'.", $startId->toString()));
                }
                throw new ClientNotFoundException(detail: sprintf("The start node with the id '%s' was not found.", $startId->toString()));
            }

            $createAtEndPermission = $this->permissionChecker->checkPermissionToElement(
                $this->authProvider->getUserUuid(),
                $endId,
                'CREATE'
            );
            if (!$createAtEndPermission) {
                $readEndPermission = $this->permissionChecker->checkPermissionToElement(
                    $this->authProvider->getUserUuid(),
                    $endId,
                    'READ'
                );
                if ($readEndPermission) {
                    throw new ClientForbiddenException(detail: sprintf("User does not have permissions to create relationship ending at node with id '%s'.", $endId->toString()));
                }
                throw new ClientNotFoundException(detail: sprintf("The end node with the id '%s' was not found.", $endId->toString()));
            }

            // ok, create relationship
            return new CreatedResponse();
        }

        return new CreatedResponse();
    }
}
