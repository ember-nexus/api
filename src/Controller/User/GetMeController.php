<?php

namespace App\Controller\User;

use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetMeController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
    ) {
    }

    #[Route(
        '/me',
        name: 'get-me',
        methods: ['GET']
    )]
    public function getMe(): Response
    {
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        return $this->elementResponseService->buildElementResponseFromUuid($userUuid);
    }
}
