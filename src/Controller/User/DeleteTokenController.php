<?php

namespace App\Controller\User;

use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use LogicException;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteTokenController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private Client $redisClient,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/token',
        name: 'delete-token',
        methods: ['DELETE']
    )]
    public function deleteToken(): Response
    {
        if ($this->authProvider->isAnonymous()) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $hashedToken = $this->authProvider->getHashedToken();
        if (null === $hashedToken) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $tokenUuid = $this->authProvider->getTokenUuid();
        if (null === $tokenUuid) {
            throw new LogicException('Token must be provided.');
        }

        $tokenElement = $this->elementManager->getElement($tokenUuid);
        if (null === $tokenElement) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        $this->elementManager->delete($tokenElement);
        $this->elementManager->flush();

        $this->redisClient->expire(
            $this->authProvider->getRedisTokenKeyFromHashedToken(
                $hashedToken
            ), 0);

        return new NoContentResponse();
    }
}
