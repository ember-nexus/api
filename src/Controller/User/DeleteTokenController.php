<?php

namespace App\Controller\User;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteTokenController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider
    ) {
    }

    #[Route(
        '/token',
        name: 'delete-token',
        methods: ['DELETE']
    )]
    public function deleteToken(): Response
    {
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        if ($this->authProvider->isAnonymous()) {
            throw new ClientUnauthorizedException();
        }

        $tokenUuid = $this->authProvider->getTokenUuid();
        if (null === $tokenUuid) {
            throw new \LogicException('Token must be provided.');
        }

        $element = $this->elementManager->getElement($tokenUuid);
        if (null === $element) {
            throw new ClientNotFoundException();
        }
        $this->elementManager->delete($element);
        $this->elementManager->flush();

        // todo: remove cached token from redis

        return new NoContentResponse();
    }
}
