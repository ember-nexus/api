<?php

declare(strict_types=1);

namespace App\Controller\User;

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

        return $this->elementResponseService->buildElementResponseFromUuid($userUuid);
    }
}
