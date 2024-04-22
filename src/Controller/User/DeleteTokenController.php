<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\NoContentResponse;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteTokenController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
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

        $tokenId = $this->authProvider->getTokenId();
        if (null === $tokenId) {
            throw new LogicException('Token must be provided.');
        }

        $tokenElement = $this->elementManager->getElement($tokenId);
        if (null === $tokenElement) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        $this->elementManager->delete($tokenElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
