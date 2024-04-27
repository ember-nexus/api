<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetTokenController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    #[Route(
        '/token',
        name: 'get-token',
        methods: ['GET']
    )]
    public function getToken(): Response
    {
        if ($this->authProvider->isAnonymous()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $tokenId = $this->authProvider->getTokenId();

        if (!$tokenId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Token uuid should not be null.');
        }

        return $this->elementResponseService->buildElementResponseFromId($tokenId);
    }
}
