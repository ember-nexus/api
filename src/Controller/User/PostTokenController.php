<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
use App\Service\RequestUtilService;
use App\Service\SecurityUtilService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostTokenController extends AbstractController
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private RequestUtilService $requestUtilService,
        private SecurityUtilService $securityUtilService,
    ) {
    }

    #[Route(
        '/token',
        name: 'post-token',
        methods: ['POST']
    )]
    public function postToken(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);
        $data = $this->requestUtilService->getDataFromBody($body);

        $this->requestUtilService->validateTypeFromBody('Token', $body);
        $uniqueUserIdentifier = $this->requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $data);

        $userElement = $this->securityUtilService->findUserByUniqueUserIdentifier($uniqueUserIdentifier);
        $userId = $userElement->getId();
        if (null === $userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $currentPassword = $this->requestUtilService->getStringFromBody('password', $body);
        $this->securityUtilService->validatePasswordMatches($userElement, $currentPassword);

        $lifetimeInSeconds = $this->getLifetimeInSecondsFromBody($body);
        $data = $this->requestUtilService->getDataFromBody($body);
        $token = $this->tokenGenerator->createNewToken($userId, $data, $lifetimeInSeconds);

        return $this->createTokenResponse($token);
    }

    private function createTokenResponse(string $token): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => '_TokenResponse',
                'token' => $token,
            ],
            201
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     */
    private function getLifetimeInSecondsFromBody(array $body): ?int
    {
        if (!array_key_exists('lifetimeInSeconds', $body)) {
            return null;
        }
        $lifetimeInSeconds = $body['lifetimeInSeconds'];
        if (!is_int($lifetimeInSeconds)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('lifetimeInSeconds', 'int', gettype($lifetimeInSeconds));
        }

        return $lifetimeInSeconds;
    }
}
