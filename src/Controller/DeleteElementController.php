<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteElementController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'deleteElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['DELETE']
    )]
    public function deleteElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::DELETE)) {
            throw new ClientNotFoundException();
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
