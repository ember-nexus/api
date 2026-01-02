<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\Response\NoContentResponse;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeleteTokenController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
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

        $tokenElement = $this->elementManager->getElementOrFail($tokenId);
        $this->elementManager->delete($tokenElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
