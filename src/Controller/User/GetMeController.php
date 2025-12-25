<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetMeController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
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
        $userId = $this->authProvider->getUserId();

        try {
            $response = $this->elementResponseService->buildElementResponseFromId($userId);
        } catch (Server500LogicErrorException $exception) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate(sprintf('Anonymous user with id %s does not exist.', $userId->toString()));
        }

        return $response;
    }
}
