<?php

namespace App\Controller\User;

use App\Exception\ClientUnauthorizedException;
use App\Response\JsonResponse;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostTokenController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private TokenGenerator $tokenGenerator
    ) {
    }

    #[Route(
        '/token',
        name: 'postToken',
        methods: ['POST']
    )]
    public function postToken(Request $request): Response
    {
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        $body = \Safe\json_decode($request->getContent(), true);
        /**
         * @var array<string, mixed> $body
         *                           #todo exception if JSON is not a document?
         */
        $lifetimeInSeconds = null;
        if (array_key_exists('lifetimeInSeconds', $body)) {
            $lifetimeInSeconds = (int) $body['lifetimeInSeconds'];
        }

        $tokenName = null;
        if (array_key_exists('name', $body)) {
            $tokenName = (string) $body['name'];
        }

        $token = $this->tokenGenerator->createNewToken($userUuid, $tokenName, $lifetimeInSeconds);

        return new JsonResponse([
            'token' => $token,
        ]);
    }
}